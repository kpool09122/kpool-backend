<?php

declare(strict_types=1);

namespace Tests\Wiki\Grading\Application\Service;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Grading\Application\Service\CollaboratorRoleTransitionService;
use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CollaboratorRoleTransitionServiceTest extends TestCase
{
    public function testPromotionRequiresBaseMembershipBeforeCreatingGroup(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $role = $this->seniorRole();
        /** @var PrincipalGroupRepositoryInterface&MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByPrincipalId')->once()->with($principalIdentifier)->andReturn([]);
        $principalGroupRepository->shouldNotReceive('save');
        /** @var PrincipalGroupFactoryInterface&MockInterface $principalGroupFactory */
        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->shouldNotReceive('create');
        $service = new CollaboratorRoleTransitionService($principalGroupRepository, $principalGroupFactory);
        $this->expectException(PrincipalGroupNotFoundException::class);
        $service->promote($principalIdentifier, $role);
    }

    public function testAlreadySeniorDoesNotCreateOrSaveGroups(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $role = $this->seniorRole();
        $group = $this->group(false);
        $group->addRole($role);
        $group->addMember($principalIdentifier);
        /** @var PrincipalGroupRepositoryInterface&MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByPrincipalId')->once()->with($principalIdentifier)->andReturn([$group]);
        $principalGroupRepository->shouldNotReceive('save');
        /** @var PrincipalGroupFactoryInterface&MockInterface $principalGroupFactory */
        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->shouldNotReceive('create');
        $service = new CollaboratorRoleTransitionService($principalGroupRepository, $principalGroupFactory);
        self::assertFalse($service->promote($principalIdentifier, $role));
    }

    public function testDemotionRemovesAllSeniorMembershipsAndPreservesBaseAndOtherMembers(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $otherPrincipal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $role = $this->seniorRole();
        $base = $this->group(true);
        $base->addMember($principalIdentifier);
        $first = $this->group(false);
        $second = $this->group(false);
        foreach ([$first, $second] as $group) {
            $group->addRole($role);
            $group->addMember($principalIdentifier);
            $group->addMember($otherPrincipal);
        }
        /** @var PrincipalGroupRepositoryInterface&MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByPrincipalId')->once()->with($principalIdentifier)->andReturn([$base, $first, $second]);
        $principalGroupRepository->shouldReceive('save')->once()->with($first);
        $principalGroupRepository->shouldReceive('save')->once()->with($second);
        /** @var PrincipalGroupFactoryInterface&MockInterface $principalGroupFactory */
        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $service = new CollaboratorRoleTransitionService($principalGroupRepository, $principalGroupFactory);
        self::assertTrue($service->demote($principalIdentifier, $role));
        self::assertTrue($base->hasMember($principalIdentifier));
        foreach ([$first, $second] as $group) {
            self::assertFalse($group->hasMember($principalIdentifier));
            self::assertTrue($group->hasMember($otherPrincipal));
            self::assertTrue($group->hasRole($role->roleIdentifier()));
        }
    }

    private function seniorRole(): Role
    {
        return new Role(new RoleIdentifier(StrTestHelper::generateUuid()), 'SeniorCollaborator', [], null, new DateTimeImmutable());
    }

    private function group(bool $isDefault): PrincipalGroup
    {
        return new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()), 'Group', $isDefault, new DateTimeImmutable());
    }
}
