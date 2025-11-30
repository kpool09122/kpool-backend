<?php

declare(strict_types=1);

namespace Source\Auth\Infrastructure\Factory;

use Source\Auth\Domain\Entity\User;
use Source\Auth\Domain\Factory\UserFactoryInterface;
use Source\Auth\Domain\ValueObject\HashedPassword;
use Source\Auth\Domain\ValueObject\PlainPassword;
use Source\Auth\Domain\ValueObject\SocialConnection;
use Source\Auth\Domain\ValueObject\SocialProfile;
use Source\Auth\Domain\ValueObject\UserName;
use Source\Shared\Application\Service\Ulid\UlidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\UserIdentifier;

readonly class UserFactory implements UserFactoryInterface
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
    ): User {
        return new User(
            new UserIdentifier($this->ulidGenerator->generate()),
            $username,
            $email,
            $language,
            null,
            HashedPassword::fromPlain($plainPassword),
            [],
            null,
        );
    }

    public function createFromSocialProfile(SocialProfile $profile): User
    {
        $username = $this->buildUserName($profile);
        $password = $this->generateRandomPassword();
        $profileImage = $profile->avatarUrl() ? new ImagePath($profile->avatarUrl()) : null;

        return new User(
            new UserIdentifier($this->ulidGenerator->generate()),
            $username,
            $profile->email(),
            Language::ENGLISH,
            $profileImage,
            HashedPassword::fromPlain($password),
            [],
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
