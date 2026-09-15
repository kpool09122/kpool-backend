<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query;

readonly class AuthenticatedIdentityReadModel
{
    /**
     * @param array<int, array<string, mixed>> $accountPolicies
     * @param SwitchableAccountReadModel[] $switchableAccounts
     */
    public function __construct(
        private string $identityIdentifier,
        private string $identityName,
        private string $email,
        private string $language,
        private ?string $profileImage,
        private ?string $accountIdentifier,
        private ?string $accountPrincipalIdentifier,
        private ?string $accountType,
        private array $accountPolicies = [],
        private ?AuthenticatedAccountSummaryReadModel $account = null,
        private ?AuthenticatedAccountReferenceReadModel $originalAccount = null,
        private ?string $delegationIdentifier = null,
        private array $switchableAccounts = [],
    ) {
    }

    public function identityIdentifier(): string
    {
        return $this->identityIdentifier;
    }

    public function identityName(): string
    {
        return $this->identityName;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function language(): string
    {
        return $this->language;
    }

    public function profileImage(): ?string
    {
        return $this->profileImage;
    }

    public function accountIdentifier(): ?string
    {
        return $this->accountIdentifier;
    }

    public function accountPrincipalIdentifier(): ?string
    {
        return $this->accountPrincipalIdentifier;
    }

    public function accountType(): ?string
    {
        return $this->accountType;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function accountPolicies(): array
    {
        return $this->accountPolicies;
    }

    public function account(): ?AuthenticatedAccountSummaryReadModel
    {
        return $this->account;
    }

    public function originalAccount(): ?AuthenticatedAccountReferenceReadModel
    {
        return $this->originalAccount;
    }

    public function delegationIdentifier(): ?string
    {
        return $this->delegationIdentifier;
    }

    /** @return SwitchableAccountReadModel[] */
    public function switchableAccounts(): array
    {
        return $this->switchableAccounts;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'identityIdentifier' => $this->identityIdentifier,
            'identityName' => $this->identityName,
            'email' => $this->email,
            'language' => $this->language,
            'profileImage' => $this->profileImage,
            'accountIdentifier' => $this->accountIdentifier,
            'accountPrincipalIdentifier' => $this->accountPrincipalIdentifier,
            'accountType' => $this->accountType,
            'accountPolicies' => $this->accountPolicies,
            'account' => $this->account?->toArray(),
            'originalAccount' => $this->originalAccount?->toArray(),
            'delegationIdentifier' => $this->delegationIdentifier,
            'switchableAccounts' => array_map(
                static fn (SwitchableAccountReadModel $account): array => $account->toArray(),
                $this->switchableAccounts,
            ),
        ];
    }
}
