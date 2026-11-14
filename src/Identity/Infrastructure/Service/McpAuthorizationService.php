<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Application\Models\Identity\Identity;
use Application\Models\Identity\McpAccessToken;
use Application\Models\Identity\McpClient;
use DateInterval;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Passport\Bridge\ClientRepository as PassportClientRepository;
use Laravel\Passport\Bridge\User;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResourceServer;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Source\Identity\Application\Exception\InvalidAuthorizationConsentException;
use Source\Identity\Application\Exception\InvalidAuthorizationTokenException;
use Source\Identity\Application\Service\AuthorizationCodeParameters;
use Source\Identity\Application\Service\AuthorizationConsentServiceInterface;
use Source\Identity\Application\Service\AuthorizationTokenVerifierInterface;
use Source\Identity\Application\Service\ValidatedAuthorizationToken;
use Source\Identity\Domain\Entity\AuthorizationConnection;
use Source\Identity\Domain\Repository\AuthorizationConnectionRepositoryInterface;
use Source\Identity\Infrastructure\Passport\AccessTokenRepository;
use Source\Identity\Infrastructure\Passport\AuthCodeRepository;
use Source\Identity\Infrastructure\Passport\IssuanceContext;
use Source\Identity\Infrastructure\Passport\RefreshTokenRepository;
use Source\Identity\Infrastructure\Passport\ScopeRepository;

/** Passport adapter for trusted consent completion (#887) and MCP verification (#888). */
readonly class McpAuthorizationService implements AuthorizationTokenVerifierInterface, AuthorizationConsentServiceInterface
{
    public function __construct(
        private AuthorizationConnectionRepositoryInterface $authorizationConnectionRepository,
        private PassportClientRepository $passportClientRepository,
        private ClientRepository $clientRepository,
        private Dispatcher $dispatcher,
        private Encrypter $encrypter,
    ) {
    }

    /**
     * Called only after #887 has authenticated the Identity and validated all current business grants.
     * @throws InvalidAuthorizationConsentException
     */
    public function createConnection(AuthorizationConnection $authorizationConnection): void
    {
        try {
            $this->persistConsent($authorizationConnection);
        } catch (OAuthServerException $exception) {
            throw new InvalidAuthorizationConsentException($exception);
        }
    }

    /** @throws InvalidAuthorizationConsentException */
    public function issueCode(string $connectionIdentifier, AuthorizationCodeParameters $authorizationCodeParameters): string
    {
        try {
            $response = $this->authorize($connectionIdentifier, (new ServerRequest('GET', '/oauth/authorize'))->withQueryParams([
                'client_id' => $authorizationCodeParameters->clientIdentifier,
                'response_type' => 'code',
                'redirect_uri' => $authorizationCodeParameters->redirectUri,
                'scope' => implode(' ', $authorizationCodeParameters->requestedScopes),
                'resource' => $authorizationCodeParameters->resource,
                'code_challenge_method' => 'S256',
                'code_challenge' => $authorizationCodeParameters->codeChallenge,
                'state' => $authorizationCodeParameters->state,
            ]));

            return $response->getHeaderLine('Location');
        } catch (OAuthServerException $exception) {
            throw new InvalidAuthorizationConsentException($exception);
        }
    }

    private function persistConsent(AuthorizationConnection $authorizationConnection): void
    {
        $client = Passport::client()->newQuery()->find($authorizationConnection->clientIdentifier);
        if ($client === null || $client->revoked || $client->confidential()
            || ! $client->hasGrantType('authorization_code') || ! $client->hasGrantType('refresh_token')
            || ! Identity::query()->whereKey((string) $authorizationConnection->identityIdentifier)->exists()
            || $authorizationConnection->revokedAt !== null) {
            throw OAuthServerException::invalidGrant('Invalid consent subject or client.');
        }
        $this->assertResource($authorizationConnection->resource);
        $this->authorizationConnectionRepository->save($authorizationConnection);
    }

    /**
     * Trusted server-side completion, NOT an unauthenticated HTTP entry point.
     * The caller owns the DB transaction containing consent creation and code issuance.
     */
    public function authorize(string $connectionIdentifier, ServerRequestInterface $request): ResponseInterface
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
        $client = McpClient::query()->find($context->connection->clientIdentifier);
        if ($client === null || ! is_string($parameters['redirect_uri'])
            || ! in_array($parameters['redirect_uri'], $client->redirect_uris, true)) {
            throw OAuthServerException::invalidRequest('redirect_uri');
        }
        $server = $this->server($context);
        $authorization = $server->validateAuthorizationRequest($request);
        $context->requireConnection(
            (string) $context->connection->identityIdentifier,
            $authorization->getClient()->getIdentifier(),
            array_map(static fn ($scope): string => $scope->getIdentifier(), $authorization->getScopes()),
        );
        $authorization->setUser(new User((string) $context->connection->identityIdentifier ?: throw OAuthServerException::invalidGrant()));
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
        foreach (['connection_id', 'authorization_connection_id', 'identity_id', 'user_id', 'account_id', 'targets'] as $parameter) {
            if (array_key_exists($parameter, $parameters)) {
                throw OAuthServerException::invalidRequest($parameter);
            }
        }
        $resource = $parameters['resource'] ?? null;
        if ($grant === 'authorization_code' || array_key_exists('resource', $parameters)) {
            $resource = $this->assertResource($resource);
        }
        $context = new IssuanceContext($this->authorizationConnectionRepository, is_string($resource) ? $resource : null);

        return $this->server($context)->respondToAccessTokenRequest($request, new Response());
    }

    /** @throws InvalidAuthorizationTokenException */
    public function verify(string $bearerToken): ValidatedAuthorizationToken
    {
        try {
            return $this->verifiedToken($bearerToken);
        } catch (OAuthServerException $exception) {
            throw new InvalidAuthorizationTokenException($exception);
        }
    }

    private function verifiedToken(string $bearerToken): ValidatedAuthorizationToken
    {
        if ($bearerToken === '') {
            throw OAuthServerException::accessDenied();
        }
        $context = new IssuanceContext($this->authorizationConnectionRepository, null);
        $server = new ResourceServer(new AccessTokenRepository($this->dispatcher, $context), $this->key('public'));
        $verified = $server->validateAuthenticatedRequest((new ServerRequest('GET', '/'))->withHeader('Authorization', 'Bearer ' . $bearerToken));
        // Parse claims only AFTER the library has verified signature, validity and persisted revocation.
        $jwt = (new Parser(new JoseEncoder()))->parse($bearerToken);
        $tokenId = $verified->getAttribute('oauth_access_token_id');
        if (! $jwt instanceof UnencryptedToken || ! is_string($tokenId)) {
            throw OAuthServerException::accessDenied();
        }
        $token = McpAccessToken::query()->find($tokenId);
        $claims = $jwt->claims();
        if ($token === null || $token->expires_at->isPast()
            || $claims->get('iss') !== config()->string('mcp_oauth.issuer')
            || $claims->get('aud') !== [config()->string('mcp_oauth.resource')]
            || $claims->get('sub') !== $token->user_id
            || $claims->get('client_id') !== $token->client_id
            || $claims->get('scopes') !== $token->scopes) {
            throw OAuthServerException::accessDenied('Invalid token binding.');
        }
        $connection = $context->requireConnection($token->user_id, $token->client_id, $token->scopes);

        return new ValidatedAuthorizationToken($tokenId, $connection, $token->scopes, $token->expires_at->toDateTimeImmutable());
    }

    private function assertResource(mixed $resource): string
    {
        if (! is_string($resource) || $resource === '' || $resource !== config()->string('mcp_oauth.resource')
            || config()->string('mcp_oauth.issuer') === '') {
            throw OAuthServerException::invalidRequest('resource');
        }

        return $resource;
    }

    private function key(string $type): CryptKey
    {
        $configured = config('passport.' . $type . '_key');
        $key = is_string($configured) && $configured !== ''
            ? str_replace('\\n', "\n", $configured)
            : 'file://' . Passport::keyPath('oauth-' . $type . '.key');

        return new CryptKey($key, null, Passport::$validateKeyPermissions);
    }

    private function server(IssuanceContext $issuanceContext): AuthorizationServer
    {
        $refreshTokenRepository = new RefreshTokenRepository($this->dispatcher, $issuanceContext);
        $server = new AuthorizationServer(
            $this->passportClientRepository,
            new AccessTokenRepository($this->dispatcher, $issuanceContext),
            new ScopeRepository($this->clientRepository, $issuanceContext),
            $this->key('private'),
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
