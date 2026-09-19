<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Factory;

use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Factory\IdentityFactoryInterface;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\SocialProfile;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;

readonly class IdentityFactory implements IdentityFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function create(
        IdentityName $identityName,
        Email $email,
        Language $language,
        ?DelegationIdentifier $delegationIdentifier = null,
        ?Identity $originalIdentity = null,
        ?IdentityIdentifier $identityIdentifier = null,
    ): Identity {
        return new Identity(
            $identityIdentifier ?? new IdentityIdentifier($this->uuidGenerator->generate()),
            $identityName,
            $email,
            $language,
            $originalIdentity?->profileImage(),
            null,
            [],
            $delegationIdentifier,
            $originalIdentity?->identityIdentifier(),
        );
    }

    public function createFromSocialProfile(SocialProfile $profile): Identity
    {
        return new Identity(
            new IdentityIdentifier($this->uuidGenerator->generate()),
            $this->buildIdentityName($profile),
            $profile->email(),
            Language::ENGLISH,
            null,
            null,
            [new SocialConnection($profile->provider(), $profile->providerUserId())],
        );
    }

    public function createDelegatedIdentity(
        Identity $originalIdentity,
        DelegationIdentifier $delegationIdentifier,
    ): Identity {
        return $this->create(
            $originalIdentity->identityName(),
            $originalIdentity->email(),
            $originalIdentity->language(),
            $delegationIdentifier,
            $originalIdentity,
        );
    }

    private function buildIdentityName(SocialProfile $profile): IdentityName
    {
        $name = $profile->name();
        if ($name === null || $name === '') {
            $name = strstr((string) $profile->email(), '@', true) ?: $profile->providerUserId();
        }

        return new IdentityName(mb_substr($name, 0, IdentityName::MAX_LENGTH));
    }
}
