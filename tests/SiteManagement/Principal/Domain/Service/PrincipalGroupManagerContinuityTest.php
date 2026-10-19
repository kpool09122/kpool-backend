<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Domain\Service;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\Entity\Role;
use Source\SiteManagement\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\Service\PrincipalGroupManagerContinuity;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalStatus;
use Source\SiteManagement\Principal\Domain\ValueObject\RoleIdentifier;
use Tests\Helper\StrTestHelper;

class PrincipalGroupManagerContinuityTest extends TestCase
{
    public function testEffectiveAdministratorMustBeActiveReachableAndNotOperator(): void
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $id = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $admin = new Role(new RoleIdentifier(StrTestHelper::generateUuid()), 'Administrator', [], null);
        $operator = new Role(new RoleIdentifier(StrTestHelper::generateUuid()), 'Operator', [], null);
        $roles = $this->createStub(RoleRepositoryInterface::class);
        $roles->method('findSystemByName')->willReturnMap([['Administrator', $admin], ['Operator', $operator]]);
        foreach (PrincipalStatus::cases() as $status) {
            foreach ([false, true] as $reachable) {
                foreach ([false, true] as $denied) {
                    foreach ([false, true] as $mixed) {
                        $principal = new Principal($id, new IdentityIdentifier(StrTestHelper::generateUuid()), $account, $status);
                        $group = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), 'Administrator', [$admin->roleIdentifier(), ...($mixed ? [$operator->roleIdentifier()] : [])], $account);
                        $group->replaceMembers([$id]);
                        $evaluator = $this->createStub(PolicyEvaluatorInterface::class);
                        $evaluator->method('evaluate')->willReturn(! $denied);
                        self::assertSame(
                            $status === PrincipalStatus::ACTIVE && $reachable && ! $denied && ! $mixed,
                            (new PrincipalGroupManagerContinuity($roles, $evaluator))->hasManager([$group], [(string) $id => $principal], static fn (): bool => $reachable)
                        );
                    }
                }
            }
        }
    }
}
