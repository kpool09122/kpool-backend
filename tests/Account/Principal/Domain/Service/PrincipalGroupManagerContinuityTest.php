<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Domain\Service;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Principal\Domain\Entity\Policy;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\Service\PrincipalGroupManagerContinuity;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Effect;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Account\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Principal\Domain\ValueObject\ResourceType;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Account\Principal\Domain\ValueObject\Statement;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class PrincipalGroupManagerContinuityTest extends TestCase
{
    public function testLoadsRolesAndPoliciesOnceWithoutLeakingAnotherPrincipalsAllow(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principals = [];
        $groups = [];
        $roles = [];
        $policies = [];
        foreach ([Effect::DENY, Effect::ALLOW] as $effect) {
            $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
            $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
            $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
            $principals[(string) $principalIdentifier] = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), $accountIdentifier);
            $group = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), $accountIdentifier, 'Managers', false, new DateTimeImmutable(), [$roleIdentifier]);
            $group->addMember($principalIdentifier);
            $groups[] = $group;
            $roles[(string) $roleIdentifier] = new Role($roleIdentifier, 'Manager', [$policyIdentifier], null);
            $policies[(string) $policyIdentifier] = new Policy($policyIdentifier, 'Management', [new Statement($effect, [Action::PRINCIPAL_GROUP_MANAGE], [ResourceType::ACCOUNT])], null, new DateTimeImmutable());
        }
        $roleRepository = $this->createMock(RoleRepositoryInterface::class);
        $roleRepository->expects(self::once())->method('findByIds')->with(self::callback(static fn (array $identifiers): bool => count($identifiers) === 2))->willReturn($roles);
        $policyRepository = $this->createMock(PolicyRepositoryInterface::class);
        $policyRepository->expects(self::once())->method('findByIds')->with(self::callback(static fn (array $identifiers): bool => count($identifiers) === 2))->willReturn($policies);
        self::assertTrue((new PrincipalGroupManagerContinuity($roleRepository, $policyRepository))->hasManager($groups, $principals, Resource::account($accountIdentifier)));
    }

    public function testCountsOnlyActiveSameAccountManagersAndDenyWins(): void
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $id = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $roleId = new RoleIdentifier(StrTestHelper::generateUuid());
        $policyId = new PolicyIdentifier(StrTestHelper::generateUuid());
        $group = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), $account, 'Managers', false, new DateTimeImmutable(), [$roleId]);
        $group->addMember($id);
        $roleRepository = $this->createStub(RoleRepositoryInterface::class);
        $roleRepository->method('findByIds')->willReturn([(string) $roleId => new Role($roleId, 'Manager', [$policyId], null)]);
        foreach ([false, true] as $deny) {
            $statements = [new Statement(Effect::ALLOW, [Action::PRINCIPAL_GROUP_MANAGE], [ResourceType::ACCOUNT])];
            if ($deny) {
                $statements[] = new Statement(Effect::DENY, [Action::PRINCIPAL_GROUP_MANAGE], [ResourceType::ACCOUNT]);
            }
            $policyRepository = $this->createStub(PolicyRepositoryInterface::class);
            $policyRepository->method('findByIds')->willReturn([(string) $policyId => new Policy($policyId, 'Management', $statements, null, new DateTimeImmutable())]);
            $service = new PrincipalGroupManagerContinuity($roleRepository, $policyRepository);
            foreach (PrincipalStatus::cases() as $status) {
                foreach ([true, false] as $sameAccount) {
                    $principal = new Principal($id, new IdentityIdentifier(StrTestHelper::generateUuid()), $sameAccount ? $account : new AccountIdentifier(StrTestHelper::generateUuid()), $status);
                    self::assertSame(! $deny && $sameAccount && $status === PrincipalStatus::ACTIVE, $service->hasManager([$group], [(string) $id => $principal], Resource::account($account)));
                }
            }
            self::assertFalse($service->hasManager([], [], Resource::account($account)));
        }
    }
}
