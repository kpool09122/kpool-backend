<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Factory;

use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Factory\IdentityFactoryInterface;
use Source\Identity\Domain\ValueObject\HashedPassword;
use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\SocialProfile;
use Source\Identity\Domain\ValueObject\UserName;
use Source\Shared\Application\Service\Ulid\UlidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;

readonly class IdentityFactory implements IdentityFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $ulidGenerator,
    ) {
    }

    public function create(
        UserName      $username,
        Email         $email,
        Language      $language,
        PlainPassword $plainPassword,
    ): Identity {
        return new Identity(
            new IdentityIdentifier($this->ulidGenerator->generate()),
            $username,
            $email,
            $language,
            null,
            HashedPassword::fromPlain($plainPassword),
            null,
        );
    }

    public function createFromSocialProfile(SocialProfile $profile): Identity
    {
        $username = $this->buildUserName($profile);
        $password = $this->generateRandomPassword();
        $profileImage = $profile->avatarUrl() ? new ImagePath($profile->avatarUrl()) : null;

        return new Identity(
            new IdentityIdentifier($this->ulidGenerator->generate()),
            $username,
            $profile->email(),
            Language::ENGLISH,
            $profileImage,
            HashedPassword::fromPlain($password),
            null,
            [new SocialConnection($profile->provider(), $profile->providerUserId())],
        );
    }

    private function buildUserName(SocialProfile $profile): UserName
    {
        $name = $profile->name();
        if ($name === null || $name === '') {
            $name = strstr((string)$profile->email(), '@', true) ?: $profile->providerUserId();
        }

        return new UserName(mb_substr($name, 0, UserName::MAX_LENGTH));
    }

    private function generateRandomPassword(): PlainPassword
    {
        $random = substr($this->ulidGenerator->generate(), 0, PlainPassword::MIN_LENGTH);

        return new PlainPassword($random);
    }
}
