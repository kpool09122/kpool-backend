<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Domain\Service;

use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Delegation\Domain\Entity\Delegation;
use Source\Account\Delegation\Domain\Exception\DelegationPrincipalGroupCreationException;
use Source\Account\Principal\Domain\Factory\PolicyFactoryInterface;
use Source\Account\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Account\Principal\Domain\Factory\RoleFactoryInterface;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Condition;
use Source\Account\Principal\Domain\ValueObject\ConditionClause;
use Source\Account\Principal\Domain\ValueObject\ConditionKey;
use Source\Account\Principal\Domain\ValueObject\ConditionOperator;
use Source\Account\Principal\Domain\ValueObject\Effect;
use Source\Account\Principal\Domain\ValueObject\ResourceType;
use Source\Account\Principal\Domain\ValueObject\Statement;

readonly class DelegationPrincipalGroupService implements DelegationPrincipalGroupServiceInterface
{
    public function __construct(
        private PrincipalGroupFactoryInterface $principalGroupFactory,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PolicyFactoryInterface $policyFactory,
        private PolicyRepositoryInterface $policyRepository,
        private RoleFactoryInterface $roleFactory,
        private RoleRepositoryInterface $roleRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private AccountRepositoryInterface $accountRepository,
    ) {
    }

    public function createFor(Delegation $delegation): void
    {
        if ($this->principalGroupRepository->findByDelegationId($delegation->delegationIdentifier()) !== null) {
            return;
        }

        $delegatorAccount = $this->accountRepository->findById($delegation->delegatorAccountIdentifier());
        if ($delegatorAccount === null) {
            throw DelegationPrincipalGroupCreationException::delegatorAccountNotFound();
        }

        $sourceAccountIdentifier = $delegation->delegateAccountIdentifier();
        $delegationIdentifier = (string) $delegation->delegationIdentifier();
        $policy = $this->policyFactory->create(
            "Delegation Policy - {$delegationIdentifier}",
            [new Statement(
                Effect::ALLOW,
                [Action::DELEGATION_ACCOUNT_SWITCH],
                [ResourceType::ACCOUNT],
                new Condition([
                    new ConditionClause(
                        ConditionKey::RESOURCE_DELEGATION_ID,
                        ConditionOperator::EQUALS,
                        $delegationIdentifier,
                    ),
                    new ConditionClause(
                        ConditionKey::RESOURCE_TARGET_ACCOUNT_ID,
                        ConditionOperator::EQUALS,
                        (string) $delegation->delegatorAccountIdentifier(),
                    ),
                ]),
            )],
            $sourceAccountIdentifier,
        );
        $this->policyRepository->save($policy);

        $role = $this->roleFactory->create(
            "Delegation Role - {$delegationIdentifier}",
            [],
            $sourceAccountIdentifier,
        );
        $role->addPolicy($policy);
        $this->roleRepository->save($role);

        $principalGroup = $this->principalGroupFactory->create(
            $delegation->delegateAccountIdentifier(),
            sprintf('Delegation - %s', $delegatorAccount->name()),
            false,
            $delegation->delegationIdentifier(),
        );
        $principalGroup->addRole($role);
        foreach ($this->principalRepository->findByAccountId($sourceAccountIdentifier) as $principal) {
            $principalGroup->addMember($principal->principalIdentifier());
        }
        $this->principalGroupRepository->save($principalGroup);
    }
}
