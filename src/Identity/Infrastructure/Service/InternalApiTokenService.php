<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Application\Models\Identity\McpAccessToken;
use Application\Passport\IssuanceContext;
use Application\Passport\SigningKeys;
use DateTimeImmutable;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Exception as JwtException;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use League\OAuth2\Server\Exception\OAuthServerException;
use SensitiveParameter;
use Source\Identity\Application\Exception\InvalidAuthorizationTokenException;
use Source\Identity\Application\Exception\InvalidInternalApiTokenConfigurationException;
use Source\Identity\Application\Service\ApiAuthenticationContext;
use Source\Identity\Application\Service\AuthorizationTokenVerifierInterface;
use Source\Identity\Application\Service\InternalApiToken;
use Source\Identity\Application\Service\InternalApiTokenServiceInterface;
use Source\Identity\Application\Service\ValidatedAuthorizationToken;
use Source\Identity\Domain\Repository\AuthorizationConnectionRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use TypeError;
use ValueError;

readonly class InternalApiTokenService implements InternalApiTokenServiceInterface
{
    public function __construct(
        private AuthorizationTokenVerifierInterface $authorizationTokenVerifier,
        private AuthorizationConnectionRepositoryInterface $authorizationConnectionRepository,
        private InternalApiAuthorizationCache $internalApiAuthorizationCache,
    ) {
    }

    /**
     * @throws InvalidAuthorizationTokenException
     * @throws InvalidInternalApiTokenConfigurationException
     */
    public function issue(#[SensitiveParameter] string $mcpBearerToken): InternalApiToken
    {
        try {
            $source = $this->authorizationTokenVerifier->verify($mcpBearerToken);
        } catch (InvalidAuthorizationTokenException) {
            // The upstream adapter's trace may contain the raw credential.
            throw new InvalidAuthorizationTokenException();
        }
        $resource = $this->resource();
        $lifetime = config()->integer('internal_api_token.lifetime_seconds');
        if ($lifetime < 1 || $lifetime > 60) {
            throw new InvalidInternalApiTokenConfigurationException('Internal API token lifetime must be between 1 and 60 seconds.');
        }
        $now = now()->toDateTimeImmutable();
        $now = $now->setTimestamp($now->getTimestamp());
        $expiresAt = min($now->modify('+' . $lifetime . ' seconds'), $source->expiresAt);
        if ($expiresAt <= $now) {
            throw new InvalidAuthorizationTokenException();
        }
        $connection = $source->connection;
        $issuer = config()->string('mcp_oauth.issuer');
        $subject = (string) $connection->identityIdentifier();
        if ($issuer === '' || $subject === '') {
            throw new InvalidAuthorizationTokenException();
        }
        $jwt = (new Builder(new JoseEncoder(), ChainedFormatter::default()))
            ->withHeader('typ', 'internal-api+jwt')
            ->issuedBy($issuer)
            ->permittedFor($resource)
            ->identifiedBy((string) Str::uuid7())
            ->relatedTo($subject)
            ->issuedAt($now)->canOnlyBeUsedAfter($now)->expiresAt($expiresAt)
            ->withClaim('source_token_id', $source->tokenIdentifier)
            ->withClaim('authorization_connection_id', (string) $connection->identifier())
            ->withClaim('client_id', (string) $connection->clientIdentifier())
            ->withClaim('scopes', array_map(static fn (AuthorizationScope $scope): string => $scope->value, $source->scopes))
            ->getToken(new Sha256(), InMemory::plainText(SigningKeys::load('private')->getKeyContents() ?: throw new InvalidInternalApiTokenConfigurationException('Missing signing key.')));

        return new InternalApiToken($jwt->toString(), $expiresAt);
    }

    /**
     * @throws InvalidAuthorizationTokenException
     * @throws InvalidInternalApiTokenConfigurationException
     */
    public function verify(#[SensitiveParameter] string $apiBearerToken): ApiAuthenticationContext
    {
        $resource = $this->resource();
        if ($apiBearerToken === '') {
            throw new InvalidAuthorizationTokenException();
        }

        try {
            $jwt = (new Parser(new JoseEncoder()))->parse($apiBearerToken);
            (new SignedWith(new Sha256(), InMemory::plainText(SigningKeys::load('public')->getKeyContents() ?: throw new InvalidInternalApiTokenConfigurationException('Missing verification key.'))))->assert($jwt);
            if (! $jwt instanceof UnencryptedToken || $jwt->headers()->get('typ') !== 'internal-api+jwt') {
                throw new InvalidAuthorizationTokenException();
            }
            $claims = $jwt->claims();
            $expiresAt = $claims->get('exp');
            $issuedAt = $claims->get('iat');
            $notBefore = $claims->get('nbf');
            $now = now()->toDateTimeImmutable();
            if ($claims->get('iss') !== config()->string('mcp_oauth.issuer') || $claims->get('aud') !== [$resource]
                || ! is_string($claims->get('jti')) || $claims->get('jti') === ''
                || ! $expiresAt instanceof DateTimeImmutable || ! $issuedAt instanceof DateTimeImmutable || ! $notBefore instanceof DateTimeImmutable
                || $expiresAt <= $now || $issuedAt > $now || $notBefore != $issuedAt
                || $expiresAt <= $issuedAt || $expiresAt->getTimestamp() - $issuedAt->getTimestamp() > 60) {
                throw new InvalidAuthorizationTokenException();
            }
            $sourceId = $claims->get('source_token_id');
            $connectionId = $claims->get('authorization_connection_id', $claims->get('authorization_consent_id'));
            $clientId = $claims->get('client_id');
            if (! is_string($sourceId) || $sourceId === '' || ! is_string($connectionId) || ! is_string($clientId)) {
                throw new InvalidAuthorizationTokenException();
            }

            $validated = $this->internalApiAuthorizationCache->resolve(
                $sourceId,
                fn (): ValidatedAuthorizationToken => $this->loadAuthorizationToken($sourceId, $now),
            );
            $connection = $validated->connection;
            if ($validated->expiresAt <= $now || $expiresAt > $validated->expiresAt
                || $connection->resource() !== config()->string('mcp_oauth.resource')
                || $connectionId !== (string) $connection->identifier()
                || $clientId !== (string) $connection->clientIdentifier()
                || $claims->get('sub') !== (string) $connection->identityIdentifier()
                || $claims->get('scopes') !== array_map(static fn (AuthorizationScope $scope): string => $scope->value, $validated->scopes)) {
                throw new InvalidAuthorizationTokenException();
            }

            return new ApiAuthenticationContext($validated, $expiresAt);
        } catch (JwtException | OAuthServerException | InvalidArgumentException | ValueError | TypeError) {
            // Do not retain parser/provider exception chains or raw credential arguments.
            throw new InvalidAuthorizationTokenException();
        }
    }

    public function forgetCachedAuthorization(string $sourceTokenIdentifier): void
    {
        $this->internalApiAuthorizationCache->forget($sourceTokenIdentifier);
    }

    /** @throws InvalidAuthorizationTokenException */
    private function loadAuthorizationToken(string $sourceId, DateTimeImmutable $now): ValidatedAuthorizationToken
    {
        $token = McpAccessToken::query()->find($sourceId);
        if ($token === null || $token->revoked || $token->expires_at->toDateTimeImmutable() <= $now) {
            throw new InvalidAuthorizationTokenException();
        }

        $context = new IssuanceContext($this->authorizationConnectionRepository, null);
        if (! $context->load(new AuthorizationConnectionIdentifier($token->authorization_connection_id))) {
            throw new InvalidAuthorizationTokenException();
        }
        $connection = $context->requireConnection($token->user_id, $token->client_id, $token->scopes);

        return new ValidatedAuthorizationToken(
            $sourceId,
            $connection,
            array_map(AuthorizationScope::from(...), $token->scopes),
            $token->expires_at->toDateTimeImmutable(),
        );
    }

    /** @return non-empty-string */
    private function resource(): string
    {
        $resource = config()->string('internal_api_token.resource');
        if ($resource === '' || $resource === config()->string('mcp_oauth.resource') || config()->string('mcp_oauth.issuer') === '') {
            throw new InvalidInternalApiTokenConfigurationException('Configure distinct MCP and internal API resources and a non-empty issuer.');
        }

        return $resource;
    }
}
