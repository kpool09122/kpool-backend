<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Principal\Application\Exception\CannotRemoveLastPrincipalGroupManagerException;
use Source\Account\Principal\Application\Service\ChildPrincipalManagerContinuityInterface;
use Source\Account\Principal\Application\Service\PrincipalReachabilityInterface;
use Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembers;
use Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersInput;
use Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersOutput;
use Source\Account\Principal\Domain\Entity\Policy;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Condition;
use Source\Account\Principal\Domain\ValueObject\ConditionClause;
use Source\Account\Principal\Domain\ValueObject\ConditionKey;
use Source\Account\Principal\Domain\ValueObject\ConditionOperator;
use Source\Account\Principal\Domain\ValueObject\Effect;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Account\Principal\Domain\ValueObject\ResourceType;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Account\Principal\Domain\ValueObject\Statement;
use Source\Account\Principal\Infrastructure\Service\PolicyEvaluator;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AccountCategoryContinuityTest extends TestCase
{
    /** @return array<string, array{bool, bool}> */
    public static function scenarios(): array
    {
        return [
            'authorization category allow' => [true, false],
            'authorization category deny' => [true, true],
            'continuity category allow' => [false, false],
            'continuity category deny' => [false, true],
        ];
    }

    #[DataProvider('scenarios')]
    public function testUsesPersistedAccountCategoryAndType(bool $authorize, bool $deny): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), $accountIdentifier);
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        $group = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), $accountIdentifier, 'Managers', false, new DateTimeImmutable(), [$roleIdentifier]);
        $group->addMember($principalIdentifier);
        $condition = new Condition([
            new ConditionClause(ConditionKey::RESOURCE_ACCOUNT_CATEGORY, ConditionOperator::EQUALS, AccountCategory::AGENCY->value),
            new ConditionClause(ConditionKey::RESOURCE_ACCOUNT_TYPE, ConditionOperator::EQUALS, AccountType::CORPORATION->value),
        ]);
        $statements = [new Statement(Effect::ALLOW, [Action::PRINCIPAL_GROUP_MANAGE], [ResourceType::ACCOUNT], $deny ? null : $condition)];
        if ($deny) {
            $statements[] = new Statement(Effect::DENY, [Action::PRINCIPAL_GROUP_MANAGE], [ResourceType::ACCOUNT], $condition);
        }
        $principalGroupRepository = $this->createMock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->method('findByAccountId')->willReturn([$group]);
        $principalGroupRepository->method('findByAccountIdAndPrincipal')->willReturn([$group]);
        $principalGroupRepository->expects(self::never())->method('save');
        $principalRepository = $this->createMock(PrincipalRepositoryInterface::class);
        $principalRepository->method('findByIds')->willReturn([(string) $principalIdentifier => $principal]);
        $principalRepository->expects(self::never())->method('save');
        $roleRepository = $this->createStub(RoleRepositoryInterface::class);
        $roleRepository->method('findByIds')->willReturn([(string) $roleIdentifier => new Role($roleIdentifier, 'Manager', [$policyIdentifier], null)]);
        $policyRepository = $this->createStub(PolicyRepositoryInterface::class);
        $policyRepository->method('findByIds')->willReturn([(string) $policyIdentifier => new Policy($policyIdentifier, 'Management', $statements, null, new DateTimeImmutable())]);
        $account = $this->createStub(Account::class);
        $account->method('type')->willReturn(AccountType::CORPORATION);
        $account->method('accountCategory')->willReturn(AccountCategory::AGENCY);
        $accountRepository = $this->createStub(AccountRepositoryInterface::class);
        $accountRepository->method('findById')->willReturn($account);
        $principalReachability = $this->createStub(PrincipalReachabilityInterface::class);
        $principalReachability->method('filterReachable')->willReturnCallback(static function (array $principals): array {
            /** @var Principal[] $principals */
            $result = [];
            foreach ($principals as $principal) {
                $result[(string) $principal->principalIdentifier()] = $principal;
            }

            return $result;
        });
        if ($authorize) {
            $policyEvaluator = new PolicyEvaluator($principalGroupRepository, $roleRepository, $policyRepository);
        } else {
            $policyEvaluator = $this->createStub(PolicyEvaluatorInterface::class);
            $policyEvaluator->method('evaluate')->willReturn(true);
        }
        $childPrincipalManagerContinuity = $this->createMock(ChildPrincipalManagerContinuityInterface::class);
        $childPrincipalManagerContinuity->expects($deny ? self::never() : self::once())->method('assertPreserved');
        if ($deny) {
            $this->expectException($authorize ? AccountUpdateForbiddenException::class : CannotRemoveLastPrincipalGroupManagerException::class);
        }
        $output = new UpdatePrincipalGroupMembersOutput();
        (new UpdatePrincipalGroupMembers($childPrincipalManagerContinuity, $principalReachability, $principalGroupRepository, $principalRepository, $roleRepository, $policyRepository, $policyEvaluator, $accountRepository))
            ->process(new UpdatePrincipalGroupMembersInput($accountIdentifier, $principal, [], AccountType::INDIVIDUAL), $output);
        self::assertSame([], $output->toArray()['principalGroups']);
    }
}
