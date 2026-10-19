<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\Service;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Principal\Domain\Service\PrincipalGroupManagerContinuity;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class PrincipalGroupManagerContinuityTest extends TestCase
{
    public function testEffectiveAdministratorMustBeActiveReachableNonDelegatedAndNotOperator(): void
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $id = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $admin = new Role(new RoleIdentifier(StrTestHelper::generateUuid()), 'Administrator', [], null, new DateTimeImmutable());
        $operator = new Role(new RoleIdentifier(StrTestHelper::generateUuid()), 'Operator', [], null, new DateTimeImmutable());
        $roles = $this->createStub(RoleRepositoryInterface::class);
        $roles->method('findSystemByName')->willReturnMap([['Administrator', $admin], ['Operator', $operator]]);
        foreach (PrincipalStatus::cases() as $status) {
            foreach ([false, true] as $delegated) {
                foreach ([false, true] as $reachable) {
                    foreach ([false, true] as $denied) {
                        foreach ([false, true] as $mixed) {
                            $principal = new Principal($id, new IdentityIdentifier(StrTestHelper::generateUuid()), $account, $delegated ? new DelegationIdentifier(StrTestHelper::generateUuid()) : null, $status);
                            $group = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), $account, 'Administrator', false, new DateTimeImmutable());
                            $group->addRole($admin);
                            if ($mixed) {
                                $group->addRole($operator);
                            }
                            $group->addMember($id);
                            $evaluator = $this->createStub(PolicyEvaluatorInterface::class);
                            $evaluator->method('evaluate')->willReturn(! $denied);
                            self::assertSame(
                                $status === PrincipalStatus::ACTIVE && ! $delegated && $reachable && ! $denied && ! $mixed,
                                (new PrincipalGroupManagerContinuity($roles, $evaluator))->hasManager([$group], [(string) $id => $principal], static fn (): bool => $reachable)
                            );
                        }
                    }
                }
            }
        }
    }
}
