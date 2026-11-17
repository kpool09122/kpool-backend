<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Application\Models\Identity\McpClient;
use Application\Passport\AccessTokenRepository;
use Application\Passport\AuthCodeRepository;
use Application\Passport\IssuanceContext;
use Application\Passport\RefreshTokenRepository;
use Application\Passport\ScopeRepository;
use Application\Passport\SigningKeys;
use DateInterval;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Bridge\ClientRepository as PassportClientRepository;
use Laravel\Passport\Bridge\User;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\Exception\InvalidAuthorizationRequestException;
use Source\Identity\Application\Service\AuthorizationCodeParameters;
use Source\Identity\Application\Service\AuthorizationConnectionServiceInterface;
use Source\Identity\Application\Service\InternalApiTokenServiceInterface;
use Source\Identity\Domain\Repository\AuthorizationConnectionRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Throwable;

/** Passport adapter for trusted consent completion and token issuance. */
readonly class McpAuthorizationService implements AuthorizationConnectionServiceInterface
{
    public function __construct(
        private AuthorizationConnectionRepositoryInterface $authorizationConnectionRepository,
        private PassportClientRepository $passportClientRepository,
        private ClientRepository $clientRepository,
        private Dispatcher $dispatcher,
        private Encrypter $encrypter,
        private InternalApiTokenServiceInterface $internalApiTokenService,
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InvalidAuthorizationRequestException */
    public function issueCode(AuthorizationConnectionIdentifier $connectionIdentifier, AuthorizationCodeParameters $authorizationCodeParameters): string
    {
        try {
            $response = $this->authorize($connectionIdentifier, (new ServerRequest('GET', '/oauth/authorize'))->withQueryParams([
                'client_id' => (string) $authorizationCodeParameters->clientIdentifier,
                'response_type' => 'code',
                'redirect_uri' => $authorizationCodeParameters->redirectUri,
                'scope' => implode(' ', array_map(static fn (AuthorizationScope $scope): string => $scope->value, $authorizationCodeParameters->requestedScopes)),
                'resource' => $authorizationCodeParameters->resource,
                'code_challenge_method' => 'S256',
                'code_challenge' => $authorizationCodeParameters->codeChallenge,
                'state' => $authorizationCodeParameters->state,
            ]));

            return $response->getHeaderLine('Location');
        } catch (OAuthServerException $exception) {
            throw new InvalidAuthorizationRequestException($exception);
        }
    }

    /**
     * Trusted server-side completion, NOT an unauthenticated HTTP entry point.
     * The caller owns the DB transaction containing connection creation and code issuance.
     */
    public function authorize(AuthorizationConnectionIdentifier $connectionIdentifier, ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getQueryParams();
        $resource = $parameters['resource'] ?? null;
        $resource = $this->assertResource($resource);
        if (($parameters['code_challenge_method'] ?? null) !== 'S256'
            || ! isset($parameters['code_challenge']) || ! isset($parameters['redirect_uri'])) {
            throw OAuthServerException::invalidRequest('code_challenge_method');
        }
        $context = new IssuanceContext($this->authorizationConnectionRepository, $resource);
        if (! $context->load($connectionIdentifier) || $context->connection === null) {
            throw OAuthServerException::invalidGrant('Inactive authorization connection.');
        }
        $client = McpClient::query()->find((string) $context->connection->clientIdentifier());
        if ($client === null || ! is_string($parameters['redirect_uri'])
            || ! in_array($parameters['redirect_uri'], $client->redirect_uris, true)) {
            throw OAuthServerException::invalidRequest('redirect_uri');
        }
        $server = $this->server($context, new AccessTokenRepository($this->dispatcher, $context));
        $authorization = $server->validateAuthorizationRequest($request);
        // State is opaque: League trims query parameters, but callbacks must echo it exactly.
        if (is_string($parameters['state'] ?? null)) {
            $authorization->setState($parameters['state']);
        }
        $context->requireConnection(
            (string) $context->connection->identityIdentifier(),
            $authorization->getClient()->getIdentifier(),
            array_map(static fn ($scope): string => $scope->getIdentifier(), $authorization->getScopes()),
        );
        $authorization->setUser(new User((string) $context->connection->identityIdentifier() ?: throw OAuthServerException::invalidGrant()));
        $authorization->setAuthorizationApproved(true);

        return $server->completeAuthorizationRequest($authorization, new Response());
    }

    /** The caller owns the transaction, including refresh rotation and old-token revocation. */
    public function exchange(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getParsedBody();
        if (! is_array($parameters)) {
            throw OAuthServerException::invalidRequest('grant_type');
        }
        $grant = $parameters['grant_type'] ?? null;
        if (! in_array($grant, ['authorization_code', 'refresh_token'], true)) {
            throw OAuthServerException::unsupportedGrantType();
        }
        if ($grant === 'authorization_code' && array_key_exists('scope', $parameters)) {
            throw OAuthServerException::invalidRequest('scope');
        }
        foreach (['authorization_connection_id', 'connection_id', 'authorization_consent_id', 'identity_id', 'user_id', 'account_id', 'targets'] as $parameter) {
            if (array_key_exists($parameter, $parameters)) {
                throw OAuthServerException::invalidRequest($parameter);
            }
        }
        $resource = $parameters['resource'] ?? null;
        if ($grant === 'authorization_code' || array_key_exists('resource', $parameters)) {
            $resource = $this->assertResource($resource);
        }
        $context = new IssuanceContext($this->authorizationConnectionRepository, is_string($resource) ? $resource : null);
        $accessTokenRepository = new AccessTokenRepository($this->dispatcher, $context);
        $response = $this->server($context, $accessTokenRepository)->respondToAccessTokenRequest($request, new Response());

        $revokedTokenIdentifier = $accessTokenRepository->revokedTokenIdentifier();
        if ($revokedTokenIdentifier !== null) {
            DB::afterCommit(function () use ($revokedTokenIdentifier): void {
                try {
                    $this->internalApiTokenService->forgetCachedAuthorization($revokedTokenIdentifier);
                } catch (Throwable $exception) {
                    $this->logger->warning('Internal API authorization cache invalidation failed after token refresh.', ['exception' => $exception]);
                }
            });
        }

        return $response;
    }

    private function assertResource(mixed $resource): string
    {
        if (! is_string($resource) || $resource === '' || $resource !== config()->string('mcp_oauth.resource')
            || config()->string('mcp_oauth.issuer') === '') {
            throw OAuthServerException::invalidRequest('resource');
        }

        return $resource;
    }

    private function server(IssuanceContext $issuanceContext, AccessTokenRepository $accessTokenRepository): AuthorizationServer
    {
        $refreshTokenRepository = new RefreshTokenRepository($this->dispatcher, $issuanceContext);
        $server = new AuthorizationServer(
            $this->passportClientRepository,
            $accessTokenRepository,
            new ScopeRepository($this->clientRepository, $issuanceContext),
            SigningKeys::load('private'),
            Passport::tokenEncryptionKey($this->encrypter),
        );
        $code = new AuthCodeGrant(
            new AuthCodeRepository($issuanceContext),
            $refreshTokenRepository,
            new DateInterval('PT' . config()->integer('mcp_oauth.authorization_code_seconds') . 'S')
        );
        $refresh = new RefreshTokenGrant($refreshTokenRepository);
        foreach ([$code, $refresh] as $grant) {
            $grant->setRefreshTokenTTL(new DateInterval('PT' . config()->integer('mcp_oauth.refresh_token_seconds') . 'S'));
            $server->enableGrantType($grant, new DateInterval('PT' . config()->integer('mcp_oauth.access_token_seconds') . 'S'));
        }
        $server->revokeRefreshTokens(true);

        return $server;
    }
}
