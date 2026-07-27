<?php

declare(strict_types=1);

namespace Tests\Account\PrincipalGroup\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use DateTimeImmutable;
use Mockery;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Principal\Application\Exception\CannotRemoveLastPrincipalGroupManagerException;
use Source\Account\Principal\Application\Exception\PrincipalAlreadyAssignedToPrincipalGroupException;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Account\Principal\Application\Exception\PrincipalNotFoundException;
use Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\PrincipalGroupMembers;
use Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembers;
use Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersInput;
use Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersInterface;
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
use Source\Account\Principal\Domain\ValueObject\Effect;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Principal\Domain\ValueObject\ResourceType;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Account\Principal\Domain\ValueObject\Statement;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class UpdatePrincipalGroupMembersTest extends TestCase
{
    public function test__construct(): void
    {
        $this->app->instance(PrincipalGroupRepositoryInterface::class, Mockery::mock(PrincipalGroupRepositoryInterface::class));
        $this->app->instance(PrincipalRepositoryInterface::class, Mockery::mock(PrincipalRepositoryInterface::class));
        $this->app->instance(RoleRepositoryInterface::class, Mockery::mock(RoleRepositoryInterface::class));
        $this->app->instance(PolicyRepositoryInterface::class, Mockery::mock(PolicyRepositoryInterface::class));
        $this->app->instance(PolicyEvaluatorInterface::class, Mockery::mock(PolicyEvaluatorInterface::class));

        $this->assertInstanceOf(UpdatePrincipalGroupMembers::class, $this->app->make(UpdatePrincipalGroupMembersInterface::class));
    }

    public function testProcessUpdatesMultipleGroupMembersAndKeepsUntargetedGroup(): void
    {
        [$accountId, $executor, $manager, $memberA, $memberB, $groupA, $groupB, $untargetedGroup, $roleId, $policyId] = $this->fixture();

        /** @var PrincipalGroupRepositoryInterface&\Mockery\MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByAccountId')->once()->andReturn([$groupA, $groupB, $untargetedGroup]);
        $principalGroupRepository->shouldReceive('save')->twice()->with(Mockery::on(static fn (PrincipalGroup $group): bool => in_array((string) $group->principalGroupIdentifier(), [(string) $groupA->principalGroupIdentifier(), (string) $groupB->principalGroupIdentifier()], true)));

        /** @var PrincipalRepositoryInterface&\Mockery\MockInterface $principalRepository */
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIds')->once()->andReturn([
            (string) $executor->principalIdentifier() => $executor,
            (string) $manager->principalIdentifier() => $manager,
            (string) $memberA->principalIdentifier() => $memberA,
            (string) $memberB->principalIdentifier() => $memberB,
        ]);
        $principalRepository->shouldNotReceive('findById');

        $useCase = new UpdatePrincipalGroupMembers(
            $principalGroupRepository,
            $principalRepository,
            $this->roleRepository($roleId, $policyId),
            $this->policyRepository($policyId),
            $this->allowedPolicyEvaluator(),
        );

        $output = new UpdatePrincipalGroupMembersOutput();
        $useCase->process(new UpdatePrincipalGroupMembersInput($accountId, $executor, [
            new PrincipalGroupMembers($groupA->principalGroupIdentifier(), [$manager->principalIdentifier(), $memberA->principalIdentifier()]),
            new PrincipalGroupMembers($groupB->principalGroupIdentifier(), [$memberB->principalIdentifier()]),
        ]), $output);

        $this->assertTrue($groupA->hasMember($manager->principalIdentifier()));
        $this->assertTrue($groupA->hasMember($memberA->principalIdentifier()));
        $this->assertFalse($groupA->hasMember($memberB->principalIdentifier()));
        $this->assertTrue($groupB->hasMember($memberB->principalIdentifier()));
        $this->assertTrue($untargetedGroup->hasMember($executor->principalIdentifier()));
        $this->assertCount(2, $output->toArray()['principalGroups']);
    }

    public function testThrowsWhenRequestedPrincipalAppearsInMultipleGroups(): void
    {
        [$accountId, $executor, $manager, , , $groupA, $groupB] = $this->fixture();

        /** @var PrincipalGroupRepositoryInterface&\Mockery\MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByAccountId')->once()->andReturn([$groupA, $groupB]);
        $principalGroupRepository->shouldNotReceive('save');

        $principalRepository = $this->emptyPrincipalRepository();
        $principalRepository->shouldNotReceive('findByIds');

        $useCase = new UpdatePrincipalGroupMembers(
            $principalGroupRepository,
            $principalRepository,
            $this->emptyRoleRepository(),
            $this->emptyPolicyRepository(),
            $this->allowedPolicyEvaluator(),
        );

        $this->expectException(PrincipalAlreadyAssignedToPrincipalGroupException::class);

        $useCase->process(new UpdatePrincipalGroupMembersInput($accountId, $executor, [
            new PrincipalGroupMembers($groupA->principalGroupIdentifier(), [$manager->principalIdentifier()]),
            new PrincipalGroupMembers($groupB->principalGroupIdentifier(), [$manager->principalIdentifier()]),
        ]), new UpdatePrincipalGroupMembersOutput());
    }

    public function testThrowsWhenRequestedPrincipalAlreadyBelongsToUntargetedGroup(): void
    {
        [$accountId, $executor, $manager, , , $groupA, , $untargetedGroup] = $this->fixture();

        /** @var PrincipalGroupRepositoryInterface&\Mockery\MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByAccountId')->once()->andReturn([$groupA, $untargetedGroup]);
        $principalGroupRepository->shouldNotReceive('save');

        $principalRepository = $this->emptyPrincipalRepository();
        $principalRepository->shouldNotReceive('findByIds');

        $useCase = new UpdatePrincipalGroupMembers(
            $principalGroupRepository,
            $principalRepository,
            $this->emptyRoleRepository(),
            $this->emptyPolicyRepository(),
            $this->allowedPolicyEvaluator(),
        );

        $this->expectException(PrincipalAlreadyAssignedToPrincipalGroupException::class);

        $useCase->process(new UpdatePrincipalGroupMembersInput($accountId, $executor, [
            new PrincipalGroupMembers($groupA->principalGroupIdentifier(), [$manager->principalIdentifier(), $executor->principalIdentifier()]),
        ]), new UpdatePrincipalGroupMembersOutput());
    }

    public function testThrowsWhenPrincipalGroupIsOutsideAccount(): void
    {
        [$accountId, $executor] = $this->fixture();
        $unknownGroupId = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());

        /** @var PrincipalGroupRepositoryInterface&\Mockery\MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByAccountId')->once()->andReturn([]);
        $principalGroupRepository->shouldNotReceive('save');

        $useCase = new UpdatePrincipalGroupMembers(
            $principalGroupRepository,
            $this->emptyPrincipalRepository(),
            $this->emptyRoleRepository(),
            $this->emptyPolicyRepository(),
            $this->allowedPolicyEvaluator(),
        );

        $this->expectException(PrincipalGroupNotFoundException::class);

        $useCase->process(new UpdatePrincipalGroupMembersInput($accountId, $executor, [new PrincipalGroupMembers($unknownGroupId, [])]), new UpdatePrincipalGroupMembersOutput());
    }

    public function testThrowsWhenPrincipalIsOutsideAccount(): void
    {
        [$accountId, $executor, , $memberA, , $groupA] = $this->fixture();
        $outsidePrincipal = new Principal($memberA->principalIdentifier(), $memberA->identityIdentifier(), new AccountIdentifier(StrTestHelper::generateUuid()));

        /** @var PrincipalGroupRepositoryInterface&\Mockery\MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByAccountId')->once()->andReturn([$groupA]);
        $principalGroupRepository->shouldNotReceive('save');

        /** @var PrincipalRepositoryInterface&\Mockery\MockInterface $principalRepository */
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIds')->once()->andReturn([
            (string) $outsidePrincipal->principalIdentifier() => $outsidePrincipal,
        ]);
        $principalRepository->shouldNotReceive('findById');

        $useCase = new UpdatePrincipalGroupMembers(
            $principalGroupRepository,
            $principalRepository,
            $this->emptyRoleRepository(),
            $this->emptyPolicyRepository(),
            $this->allowedPolicyEvaluator(),
        );

        $this->expectException(PrincipalNotFoundException::class);

        $useCase->process(new UpdatePrincipalGroupMembersInput($accountId, $executor, [new PrincipalGroupMembers($groupA->principalGroupIdentifier(), [$memberA->principalIdentifier()])]), new UpdatePrincipalGroupMembersOutput());
    }

    public function testThrowsWhenRequestedPrincipalDoesNotExist(): void
    {
        [$accountId, $executor, , $memberA, , $groupA] = $this->fixture();

        /** @var PrincipalGroupRepositoryInterface&\Mockery\MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByAccountId')->once()->andReturn([$groupA]);
        $principalGroupRepository->shouldNotReceive('save');

        /** @var PrincipalRepositoryInterface&\Mockery\MockInterface $principalRepository */
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIds')->once()->andReturn([]);
        $principalRepository->shouldNotReceive('findById');

        $useCase = new UpdatePrincipalGroupMembers(
            $principalGroupRepository,
            $principalRepository,
            $this->emptyRoleRepository(),
            $this->emptyPolicyRepository(),
            $this->allowedPolicyEvaluator(),
        );

        $this->expectException(PrincipalNotFoundException::class);

        $useCase->process(new UpdatePrincipalGroupMembersInput($accountId, $executor, [new PrincipalGroupMembers($groupA->principalGroupIdentifier(), [$memberA->principalIdentifier()])]), new UpdatePrincipalGroupMembersOutput());
    }

    public function testThrowsWhenExecutorCannotManagePrincipalGroups(): void
    {
        [$accountId, $executor] = $this->fixture();
        /** @var PolicyEvaluatorInterface&\Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturnFalse();

        /** @var PrincipalGroupRepositoryInterface&\Mockery\MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldNotReceive('findByAccountId');
        $principalGroupRepository->shouldNotReceive('save');

        $useCase = new UpdatePrincipalGroupMembers(
            $principalGroupRepository,
            $this->emptyPrincipalRepository(),
            $this->emptyRoleRepository(),
            $this->emptyPolicyRepository(),
            $policyEvaluator,
        );

        $this->expectException(AccountUpdateForbiddenException::class);

        $useCase->process(new UpdatePrincipalGroupMembersInput($accountId, $executor, []), new UpdatePrincipalGroupMembersOutput());
    }

    public function testThrowsWhenNoManagerRemainsAfterUpdate(): void
    {
        [$accountId, $executor, $manager, $memberA, , $groupA, , , $roleId, $policyId] = $this->fixture();

        /** @var PrincipalGroupRepositoryInterface&\Mockery\MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByAccountId')->once()->andReturn([$groupA]);
        $principalGroupRepository->shouldNotReceive('save');

        /** @var PrincipalRepositoryInterface&\Mockery\MockInterface $principalRepository */
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIds')->once()->andReturn([
            (string) $memberA->principalIdentifier() => $memberA,
        ]);
        $principalRepository->shouldNotReceive('findById');

        /** @var PolicyRepositoryInterface&\Mockery\MockInterface $policyRepository */
        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);
        $policyRepository->shouldReceive('findByIds')->andReturn([]);

        $useCase = new UpdatePrincipalGroupMembers(
            $principalGroupRepository,
            $principalRepository,
            $this->roleRepositoryWithoutPolicies($roleId),
            $policyRepository,
            $this->allowedPolicyEvaluator(),
        );

        $this->expectException(CannotRemoveLastPrincipalGroupManagerException::class);

        $useCase->process(new UpdatePrincipalGroupMembersInput($accountId, $executor, [
            new PrincipalGroupMembers($groupA->principalGroupIdentifier(), [$memberA->principalIdentifier()]),
        ]), new UpdatePrincipalGroupMembersOutput());
    }

    public function testThrowsWhenRemainingManagerHasDenyStatement(): void
    {
        [$accountId, $executor, $manager, , , $groupA, , , $roleId, $allowPolicyId] = $this->fixture();
        $denyPolicyId = new PolicyIdentifier(StrTestHelper::generateUuid());

        /** @var PrincipalGroupRepositoryInterface&\Mockery\MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByAccountId')->once()->andReturn([$groupA]);
        $principalGroupRepository->shouldNotReceive('save');

        /** @var PrincipalRepositoryInterface&\Mockery\MockInterface $principalRepository */
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIds')->once()->andReturn([
            (string) $manager->principalIdentifier() => $manager,
        ]);
        $principalRepository->shouldNotReceive('findById');

        /** @var RoleRepositoryInterface&\Mockery\MockInterface $roleRepository */
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByIds')->andReturn([
            (string) $roleId => new Role($roleId, 'Manager', [$allowPolicyId, $denyPolicyId], false),
        ]);

        /** @var PolicyRepositoryInterface&\Mockery\MockInterface $policyRepository */
        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);
        $policyRepository->shouldReceive('findByIds')->andReturn([
            (string) $allowPolicyId => new Policy(
                $allowPolicyId,
                'Allow manage principal groups',
                [new Statement(Effect::ALLOW, [Action::PRINCIPAL_GROUP_MANAGE], [ResourceType::ACCOUNT])],
                false,
                new DateTimeImmutable(),
            ),
            (string) $denyPolicyId => new Policy(
                $denyPolicyId,
                'Deny manage principal groups',
                [new Statement(Effect::DENY, [Action::PRINCIPAL_GROUP_MANAGE], [ResourceType::ACCOUNT])],
                false,
                new DateTimeImmutable(),
            ),
        ]);

        $useCase = new UpdatePrincipalGroupMembers(
            $principalGroupRepository,
            $principalRepository,
            $roleRepository,
            $policyRepository,
            $this->allowedPolicyEvaluator(),
        );

        $this->expectException(CannotRemoveLastPrincipalGroupManagerException::class);

        $useCase->process(new UpdatePrincipalGroupMembersInput($accountId, $executor, [
            new PrincipalGroupMembers($groupA->principalGroupIdentifier(), [$manager->principalIdentifier()]),
        ]), new UpdatePrincipalGroupMembersOutput());
    }

    /** @return array{AccountIdentifier, Principal, Principal, Principal, Principal, PrincipalGroup, PrincipalGroup, PrincipalGroup, RoleIdentifier, PolicyIdentifier} */
    private function fixture(): array
    {
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $executor = $this->principal($accountId);
        $manager = $this->principal($accountId);
        $memberA = $this->principal($accountId);
        $memberB = $this->principal($accountId);
        $roleId = new RoleIdentifier(StrTestHelper::generateUuid());
        $policyId = new PolicyIdentifier(StrTestHelper::generateUuid());

        $groupA = $this->principalGroup($accountId, $roleId);
        $groupA->addMember($manager->principalIdentifier());
        $groupA->addMember($memberB->principalIdentifier());
        $groupB = $this->principalGroup($accountId);
        $groupB->addMember($memberA->principalIdentifier());
        $untargetedGroup = $this->principalGroup($accountId, $roleId);
        $untargetedGroup->addMember($executor->principalIdentifier());

        return [$accountId, $executor, $manager, $memberA, $memberB, $groupA, $groupB, $untargetedGroup, $roleId, $policyId];
    }

    private function principal(AccountIdentifier $accountId): Principal
    {
        return new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), $accountId);
    }

    private function principalGroup(AccountIdentifier $accountId, ?RoleIdentifier $roleId = null): PrincipalGroup
    {
        $principalGroup = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), $accountId, 'Test Group', false, new DateTimeImmutable());
        if ($roleId !== null) {
            $principalGroup->addRole($roleId);
        }

        return $principalGroup;
    }

    private function allowedPolicyEvaluator(): PolicyEvaluatorInterface
    {
        /** @var PolicyEvaluatorInterface&\Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->with(Mockery::type(Principal::class), Action::PRINCIPAL_GROUP_MANAGE, Mockery::type(Resource::class))->andReturnTrue();

        return $policyEvaluator;
    }

    private function roleRepository(RoleIdentifier $roleId, PolicyIdentifier $policyId): RoleRepositoryInterface
    {
        /** @var RoleRepositoryInterface&\Mockery\MockInterface $roleRepository */
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByIds')->andReturn([(string) $roleId => new Role($roleId, 'Manager', [$policyId], false)]);

        return $roleRepository;
    }

    private function policyRepository(PolicyIdentifier $policyId): PolicyRepositoryInterface
    {
        /** @var PolicyRepositoryInterface&\Mockery\MockInterface $policyRepository */
        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);
        $policyRepository->shouldReceive('findByIds')->andReturn([(string) $policyId => new Policy(
            $policyId,
            'Manage principal groups',
            [new Statement(Effect::ALLOW, [Action::PRINCIPAL_GROUP_MANAGE], [ResourceType::ACCOUNT])],
            false,
            new DateTimeImmutable(),
        )]);

        return $policyRepository;
    }

    private function roleRepositoryWithoutPolicies(RoleIdentifier $roleId): RoleRepositoryInterface
    {
        /** @var RoleRepositoryInterface&\Mockery\MockInterface $roleRepository */
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByIds')->andReturn([(string) $roleId => new Role($roleId, 'Member', [], false)]);

        return $roleRepository;
    }

    private function emptyPrincipalRepository(): PrincipalRepositoryInterface
    {
        /** @var PrincipalRepositoryInterface&\Mockery\MockInterface $principalRepository */
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);

        return $principalRepository;
    }

    private function emptyRoleRepository(): RoleRepositoryInterface
    {
        /** @var RoleRepositoryInterface&\Mockery\MockInterface $roleRepository */
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);

        return $roleRepository;
    }

    private function emptyPolicyRepository(): PolicyRepositoryInterface
    {
        /** @var PolicyRepositoryInterface&\Mockery\MockInterface $policyRepository */
        $policyRepository = Mockery::mock(PolicyRepositoryInterface::class);

        return $policyRepository;
    }
}
