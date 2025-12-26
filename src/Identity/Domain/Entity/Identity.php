<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use Source\Identity\Domain\ValueObject\HashedPassword;
use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\UserName;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;

class Identity
{
    /**
     * @param IdentityIdentifier $identityIdentifier
     * @param UserName $username
     * @param Email $email
     * @param Language $language
     * @param ?ImagePath $profileImage
     * @param HashedPassword $hashedPassword
     * @param DateTimeImmutable|null $emailVerifiedAt
     * @param SocialConnection[] $socialConnections
     */
    public function __construct(
        private readonly IdentityIdentifier $identityIdentifier,
        private UserName                    $username,
        private Email                       $email,
        private Language                    $language,
        private ?ImagePath                  $profileImage,
        private HashedPassword              $hashedPassword,
        private ?DateTimeImmutable          $emailVerifiedAt,
        private array                       $socialConnections = [],
    ) {
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function username(): UserName
    {
        return $this->username;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function profileImage(): ?ImagePath
    {
        return $this->profileImage;
    }

    public function setProfileImage(ImagePath $image): void
    {
        $this->profileImage = $image;
    }

    public function hashedPassword(): HashedPassword
    {
        return $this->hashedPassword;
    }

    public function emailVerifiedAt(): ?DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    /**
     * @param AuthCodeSession $session
     * @return void
     * @throws UnauthorizedException
     */
    public function copyEmailVerifiedAt(AuthCodeSession $session): void
    {
        if ($session->verifiedAt() === null) {
            throw new UnauthorizedException('認証されていないメールアドレスです');
        }
        $this->emailVerifiedAt = $session->verifiedAt();
    }

    /**
     * @throws DomainException
     * @return void
     */
    public function isEmailVerified(): void
    {
        if ($this->emailVerifiedAt === null) {
            throw new DomainException('メールアドレスまたはパスワードが正しくありません');
        }
    }

    /**
     * @param PlainPassword $plainPassword
     * @return void
     * @throws DomainException
     */
    public function verifyPassword(PlainPassword $plainPassword): void
    {
        if (! password_verify((string)$plainPassword, (string)$this->hashedPassword)) {
            throw new DomainException('メールアドレスまたはパスワードが正しくありません');
        }
    }

    /**
     * @return SocialConnection[]
     */
    public function socialConnections(): array
    {
        return $this->socialConnections;
    }

    public function addSocialConnection(SocialConnection $connection): void
    {
        foreach ($this->socialConnections as $existing) {
            if ($existing->equals($connection)) {
                throw new DomainException('Social connection already exists.');
            }
        }

        $this->socialConnections[] = $connection;
    }

    public function hasSocialConnection(SocialConnection $connection): bool
    {
        return array_any(
            $this->socialConnections,
            static fn (SocialConnection $existing) => $existing->equals($connection)
        );
    }
}
