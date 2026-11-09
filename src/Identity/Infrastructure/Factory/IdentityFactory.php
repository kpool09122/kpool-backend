<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Factory;

use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Factory\IdentityFactoryInterface;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\SocialProfile;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\SubdivisionCode;

readonly class IdentityFactory implements IdentityFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $ulidGenerator,
    ) {
    }

    public function create(
        IdentityName $identityName,
        Email $email,
        Language $language,
        ?CountryCode $registrationCountry = null,
        ?SubdivisionCode $registrationRegion = null,
    ): Identity {
        return new Identity(
            new IdentityIdentifier($this->ulidGenerator->generate()),
            $identityName,
            $email,
            $language,
            null,
            null,
            registrationCountry: $registrationCountry,
            registrationRegion: $registrationRegion,
        );
    }

    public function createFromSocialProfile(SocialProfile $profile, ?CountryCode $registrationCountry = null, ?SubdivisionCode $registrationRegion = null): Identity
    {
        $identityName = $this->buildIdentityName($profile);

        return new Identity(
            new IdentityIdentifier($this->ulidGenerator->generate()),
            $identityName,
            $profile->email(),
            Language::ENGLISH,
            null,
            null,
            [new SocialConnection($profile->provider(), $profile->providerUserId())],
            registrationCountry: $registrationCountry,
            registrationRegion: $registrationRegion,
        );
    }

    private function buildIdentityName(SocialProfile $profile): IdentityName
    {
        $name = $profile->name();
        if ($name === null || $name === '') {
            $name = strstr((string)$profile->email(), '@', true) ?: $profile->providerUserId();
        }

        return new IdentityName(mb_substr($name, 0, IdentityName::MAX_LENGTH));
    }
}
