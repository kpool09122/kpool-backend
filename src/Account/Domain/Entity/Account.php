<?php

declare(strict_types=1);

namespace Source\Account\Domain\Entity;

use DomainException;
use Source\Account\Domain\Exception\AccountMembershipNotFoundException;
use Source\Account\Domain\Exception\DisallowedToWithdrawByOwnerException;
use Source\Account\Domain\ValueObject\AccountIdentifier;
use Source\Account\Domain\ValueObject\AccountName;
use Source\Account\Domain\ValueObject\AccountRole;
use Source\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Domain\ValueObject\AccountType;
use Source\Account\Domain\ValueObject\ContractInfo;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\UserIdentifier;

class Account
{
    /**
     * @param AccountIdentifier $accountIdentifier
     * @param Email $email
     * @param AccountType $type
     * @param AccountName $name
     * @param ContractInfo $contractInfo
     * @param AccountStatus $status
     * @param list<AccountMembership> $memberships
     */
    public function __construct(
        private readonly AccountIdentifier $accountIdentifier,
        private readonly Email $email,
        private AccountType $type,
        private AccountName $name,
        private ContractInfo $contractInfo,
        private AccountStatus $status,
        private array $memberships,
    ) {
        $this->assertHasOwner();
        $this->assertUniqueMembers();
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function type(): AccountType
    {
        return $this->type;
    }

    public function name(): AccountName
    {
        return $this->name;
    }

    public function contractInfo(): ContractInfo
    {
        return $this->contractInfo;
    }

    public function status(): AccountStatus
    {
        return $this->status;
    }

    /**
     * @return AccountMembership[]
     */
    public function memberships(): array
    {
        return $this->memberships;
    }

    public function attachMember(AccountMembership $membership): void
    {
        $newMemberships = [...$this->memberships, $membership];
        $this->assertUniqueMembers($newMemberships);
        $this->memberships = $newMemberships;
    }

    /**
     * @throws DisallowedToWithdrawByOwnerException
     * @throws AccountMembershipNotFoundException
     */
    public function detachMember(AccountMembership $removeMembership): void
    {
        $storedMembership = $this->findMembershipByUserIdentifier($removeMembership->userIdentifier());

        if (! $storedMembership) {
            throw new AccountMembershipNotFoundException();
        }

        if ($storedMembership->role() === AccountRole::OWNER) {
            throw new DisallowedToWithdrawByOwnerException();
        }

        $updatedMemberships = array_values(
            array_filter(
                $this->memberships,
                static fn (AccountMembership $membership) => (string)$membership->userIdentifier() !== (string)$removeMembership->userIdentifier()
            )
        );
        $this->assertHasOwner($updatedMemberships);
        $this->memberships = $updatedMemberships;
    }

    /**
     * @param AccountMembership[]|null $memberships
     */
    private function assertHasOwner(?array $memberships = null): void
    {
        $memberships ??= $this->memberships;
        $hasOwner = array_any(
            $memberships,
            static fn (AccountMembership $membership) => $membership->role() === AccountRole::OWNER
        );

        if (! $hasOwner) {
            throw new DomainException('Account must have at least one owner.');
        }
    }

    /**
     * @param AccountMembership[]|null $memberships
     */
    private function assertUniqueMembers(?array $memberships = null): void
    {
        $memberships ??= $this->memberships;
        $ids = array_map(
            static fn (AccountMembership $membership) => (string)$membership->userIdentifier(),
            $memberships
        );
        if (count($ids) !== count(array_unique($ids))) {
            throw new DomainException('Duplicate account membership detected.');
        }
    }

    private function findMembershipByUserIdentifier(UserIdentifier $userIdentifier): ?AccountMembership
    {
        return array_find($this->memberships, fn ($membership) => (string)$membership->userIdentifier() === (string)$userIdentifier);

    }
}
