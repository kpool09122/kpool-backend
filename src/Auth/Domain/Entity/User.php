<?php

declare(strict_types=1);

namespace Source\Auth\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use Source\Auth\Domain\ValueObject\HashedPassword;
use Source\Auth\Domain\ValueObject\PlainPassword;
use Source\Auth\Domain\ValueObject\ServiceRole;
use Source\Auth\Domain\ValueObject\UserIdentifier;
use Source\Auth\Domain\ValueObject\UserName;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;

class User
{
    /**
     * @param UserIdentifier $userIdentifier
     * @param UserName $username
     * @param Email $email
     * @param ?ImagePath $profileImage
     * @param HashedPassword $hashedPassword
     * @param list<ServiceRole> $serviceRoles
     * @param DateTimeImmutable|null $emailVerifiedAt
     */
    public function __construct(
        private readonly UserIdentifier $userIdentifier,
        private UserName                $username,
        private Email                   $email,
        private Language                $language,
        private ?ImagePath              $profileImage,
        private HashedPassword          $hashedPassword,
        private array                   $serviceRoles,
        private ?DateTimeImmutable      $emailVerifiedAt,
    ) {
    }

    public function userIdentifier(): UserIdentifier
    {
        return $this->userIdentifier;
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
     * @return ServiceRole[]
     */
    public function serviceRoles(): array
    {
        return $this->serviceRoles;
    }

    /**
     * あるサービスのロール一覧を返す
     *
     * @return ServiceRole[]
     */
    public function rolesForService(string $service): array
    {
        return array_filter(
            $this->serviceRoles,
            static fn (ServiceRole $serviceRole) => $serviceRole->service() === $service
        );
    }

    public function hasRole(ServiceRole $targetRole): bool
    {
        return array_any(
            $this->serviceRoles,
            fn ($serviceRole) => $serviceRole->service() === $targetRole->service() && $serviceRole->role() === $targetRole->role()
        );
    }
}
