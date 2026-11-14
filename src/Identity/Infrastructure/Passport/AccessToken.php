<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Passport;

use DateTimeImmutable;
use Laravel\Passport\Bridge\AccessToken as PassportAccessToken;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Server\CryptKeyInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use LogicException;

class AccessToken extends PassportAccessToken
{
    private ?CryptKeyInterface $signingKey = null;

    /** @param ScopeEntityInterface[] $scopes
     * @param non-empty-string|null $userIdentifier
     */
    public function __construct(?string $userIdentifier, array $scopes, ClientEntityInterface $client, private readonly string $resource, private readonly string $issuer)
    {
        parent::__construct($userIdentifier, $scopes, $client);
    }

    public function setPrivateKey(CryptKeyInterface $privateKey): void
    {
        $this->signingKey = $privateKey;
    }

    public function toString(): string
    {
        $contents = $this->signingKey?->getKeyContents();
        $subject = $this->getUserIdentifier();
        if ($contents === null || $contents === '' || $subject === null || $subject === '' || $this->issuer === '' || $this->resource === '') {
            throw new LogicException('Missing token signing configuration.');
        }
        $configuration = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($contents),
            InMemory::plainText('unused'),
        );

        return $configuration->builder()
            ->issuedBy($this->issuer)
            ->permittedFor($this->resource)
            ->identifiedBy($this->getIdentifier())
            ->relatedTo($subject)
            ->issuedAt(new DateTimeImmutable())
            ->canOnlyBeUsedAfter(new DateTimeImmutable())
            ->expiresAt($this->getExpiryDateTime())
            ->withClaim('client_id', $this->getClient()->getIdentifier())
            ->withClaim('scopes', $this->getScopes())
            ->getToken($configuration->signer(), $configuration->signingKey())->toString();
    }
}
