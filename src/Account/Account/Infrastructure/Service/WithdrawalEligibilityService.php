<?php

declare(strict_types=1);

namespace Source\Account\Account\Infrastructure\Service;

use Source\Account\Account\Application\Service\WithdrawalEligibilityServiceInterface;
use Source\Account\Account\Application\UseCase\Query\WithdrawalMemberships;
use Source\Account\Account\Domain\Exception\IdentityWithdrawalNotAllowedException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class WithdrawalEligibilityService implements WithdrawalEligibilityServiceInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private RoleRepositoryInterface $roleRepository,
        private IdentityRepositoryInterface $identityRepository,
    ) {
    }

    public function canWithdraw(IdentityIdentifier $identityIdentifier): bool
    {
        try {
            $this->resolve($identityIdentifier);
        } catch (IdentityWithdrawalNotAllowedException) {
            return false;
        }

        return true;
    }

    public function resolve(IdentityIdentifier $identityIdentifier): WithdrawalMemberships
    {
        $principals = $this->principalRepository->findAllByIdentityIdentifier($identityIdentifier);
        if ($principals === []) {
            throw new IdentityWithdrawalNotAllowedException('An account membership is required for self-service withdrawal.');
        }
        $identity = $this->identityRepository->findById($identityIdentifier);
        if ($identity === null) {
            throw new IdentityWithdrawalNotAllowedException('The identity no longer exists.');
        }
        $identityEmail = (string) $identity->email();
        $accountIdentifiers = [];
        foreach ($principals as $principal) {
            $accountIdentifier = $principal->accountIdentifier();
            $accountIdentifiers[(string) $accountIdentifier] = $accountIdentifier;
        }
        $accounts = [];
        foreach ($this->accountRepository->findByIds(array_values($accountIdentifiers)) as $account) {
            $accounts[(string) $account->accountIdentifier()] = $account;
        }
        $principalIdentifiers = [];
        foreach ($principals as $principal) {
            $principalIdentifiers[] = $principal->principalIdentifier();
        }
        $principalGroups = $this->principalGroupRepository->findByPrincipalIds($principalIdentifiers);
        $ownerRole = $this->roleRepository->findSystemByName(Role::OWNER);

        // Validate every actual membership before creating any Account archive.
        foreach ($principals as $principal) {
            $accountId = (string) $principal->accountIdentifier();
            $account = $accounts[$accountId] ?? null;
            if ($account === null) {
                throw new IdentityWithdrawalNotAllowedException('The account membership no longer exists.');
            }
            if ($account->type() === AccountType::CORPORATION && strcasecmp((string) $account->email(), $identityEmail) === 0) {
                throw new IdentityWithdrawalNotAllowedException('An identity sharing the corporate account email cannot use self-service withdrawal.');
            }
            $isSystemOwner = $ownerRole !== null && array_any(
                $principalGroups,
                static fn (PrincipalGroup $principalGroup): bool => $principalGroup->hasMember($principal->principalIdentifier())
                    && $principalGroup->hasRole($ownerRole->roleIdentifier()),
            );
            if ($account->accountCategory() !== AccountCategory::GENERAL || $account->type() === null
                || ($account->type() === AccountType::CORPORATION && $isSystemOwner)) {
                throw new IdentityWithdrawalNotAllowedException('This account membership does not permit self-service withdrawal.');
            }
        }

        return new WithdrawalMemberships($principals, array_values($accounts));
    }
}
