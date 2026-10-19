<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Service;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Principal\Domain\Service\PrincipalGroupManagerContinuity;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Wiki\Principal\Infrastructure\Service\ReachablePrincipalGroupManager;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class ReachablePrincipalGroupManagerTest extends TestCase
{
    public function testUsesReachableIdentitiesAndTreatsAccountsWithoutGroupsAsPreserved(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, $identityIdentifier, $accountIdentifier);
        $group = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), $accountIdentifier, 'Managers', false, new DateTimeImmutable(), [$roleIdentifier]);
        $group->addMember($principalIdentifier);
        $principalGroupRepository = $this->createMock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->expects(self::exactly(3))->method('findByAccountId')->with($accountIdentifier)->willReturnOnConsecutiveCalls([], [$group], [$group]);
        $principalGroupRepository->expects(self::never())->method('save');
        $principalRepository = $this->createMock(PrincipalRepositoryInterface::class);
        $principalRepository->expects(self::exactly(2))->method('findByIds')->with([$principalIdentifier])->willReturn([(string) $principalIdentifier => $principal]);
        $principalRepository->expects(self::never())->method('save');
        $roleRepository = $this->createStub(RoleRepositoryInterface::class);
        $administrator = $this->createStub(Role::class);
        $administrator->method('roleIdentifier')->willReturn($roleIdentifier);
        $roleRepository->method('findSystemByName')->willReturnCallback(static fn (string $name): ?Role => $name === 'Administrator' ? $administrator : null);
        $policyEvaluator = $this->createStub(PolicyEvaluatorInterface::class);
        $policyEvaluator->method('evaluate')->willReturn(true);
        $service = new ReachablePrincipalGroupManager($principalGroupRepository, $principalRepository, new PrincipalGroupManagerContinuity($roleRepository, $policyEvaluator));
        self::assertTrue($service->isPreserved($accountIdentifier, []));
        self::assertFalse($service->isPreserved($accountIdentifier, [new IdentityIdentifier(StrTestHelper::generateUuid())]));
        self::assertTrue($service->isPreserved($accountIdentifier, [new IdentityIdentifier((string) $identityIdentifier)]));
    }
}
