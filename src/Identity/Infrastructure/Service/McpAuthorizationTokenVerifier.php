<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Application\Models\Identity\McpAccessToken;
use Application\Passport\AccessTokenRepository;
use Application\Passport\IssuanceContext;
use Application\Passport\SigningKeys;
use Illuminate\Contracts\Events\Dispatcher;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Nyholm\Psr7\ServerRequest;
use Source\Identity\Application\Exception\InvalidAuthorizationTokenException;
use Source\Identity\Application\Service\AuthorizationTokenVerifierInterface;
use Source\Identity\Application\Service\ValidatedAuthorizationToken;
use Source\Identity\Domain\Repository\AuthorizationConnectionRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationScope;

readonly class McpAuthorizationTokenVerifier implements AuthorizationTokenVerifierInterface
{
    public function __construct(
        private AuthorizationConnectionRepositoryInterface $authorizationConnectionRepository,
        private Dispatcher $dispatcher,
    ) {
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
        $server = new ResourceServer(new AccessTokenRepository($this->dispatcher, $context), SigningKeys::load('public'));
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

        return new ValidatedAuthorizationToken(
            $tokenId,
            $connection,
            array_map(AuthorizationScope::from(...), $token->scopes),
            $token->expires_at->toDateTimeImmutable(),
        );
    }
}
