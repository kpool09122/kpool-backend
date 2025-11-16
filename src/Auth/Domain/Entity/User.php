<?php

declare(strict_types=1);

namespace Source\Auth\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use Source\Auth\Domain\ValueObject\HashedPassword;
use Source\Auth\Domain\ValueObject\PlainPassword;
use Source\Auth\Domain\ValueObject\ServiceRole;
use Source\Auth\Domain\ValueObject\UserIdentifier;
use Source\Shared\Domain\ValueObject\Email;

class User
{
    /**
     * @param UserIdentifier $userIdentifier
     * @param Email $email
     * @param HashedPassword $hashedPassword
     * @param list<ServiceRole> $serviceRoles
     * @param DateTimeImmutable|null $emailVerifiedAt
     */
    public function __construct(
        private readonly UserIdentifier $userIdentifier,
        private Email                   $email,
        private HashedPassword          $hashedPassword,
        private array                   $serviceRoles,
        private ?DateTimeImmutable      $emailVerifiedAt,
    ) {
    }

    public function userIdentifier(): UserIdentifier
    {
        return $this->userIdentifier;
    }

    public function email(): Email
    {
        return $this->email;
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
