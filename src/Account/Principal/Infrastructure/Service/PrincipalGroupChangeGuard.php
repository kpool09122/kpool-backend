<?php

declare(strict_types=1);

namespace Source\Account\Principal\Infrastructure\Service;

use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Principal\Application\Exception\CannotRemoveLastPrincipalGroupManagerException;
use Source\Account\Principal\Application\Service\ChildPrincipalManagerContinuityInterface;
use Source\Account\Principal\Application\Service\PrincipalGroupChangeGuardInterface;
use Source\Account\Principal\Application\Service\PrincipalReachabilityInterface;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Service\PrincipalGroupManagerContinuity;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class PrincipalGroupChangeGuard implements PrincipalGroupChangeGuardInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private AccountRepositoryInterface $accountRepository,
        private PrincipalGroupManagerContinuity $principalGroupManagerContinuity,
        private PrincipalReachabilityInterface $principalReachability,
        private ChildPrincipalManagerContinuityInterface $childPrincipalManagerContinuity,
    ) {
    }

    /** @param PrincipalGroup[] $groups */
    public function assertPreserved(AccountIdentifier $accountIdentifier, array $groups): void
    {
        $allPrincipals = $this->principalRepository->findByAccountId($accountIdentifier);
        $principals = $this->principalReachability->filterReachable($allPrincipals);
        $account = $this->accountRepository->findById($accountIdentifier);
        if ($account === null || ! $this->principalGroupManagerContinuity->hasManager($groups, $principals, Resource::account($accountIdentifier, $account->type(), $account->accountCategory()))) {
            throw new CannotRemoveLastPrincipalGroupManagerException();
        }
        $principalsById = [];
        foreach ($allPrincipals as $principal) {
            $principalsById[(string) $principal->principalIdentifier()] = $principal;
        }
        $this->childPrincipalManagerContinuity->assertPreserved($accountIdentifier, $principalsById, $groups);
    }
}
