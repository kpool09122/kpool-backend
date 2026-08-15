<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use DateTimeImmutable;
use Mockery;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Principal\Domain\Entity\Principal as AccountPrincipal;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface as AccountPrincipalRepositoryInterface;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier as AccountPrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Application\Exception\CannotRemoveLastWikiAdministratorException;
use Source\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\PrincipalGroupMembers;
use Source\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembers;
use Source\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersInput;
use Source\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersOutput;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class UpdatePrincipalGroupMembersTest extends TestCase
{
    public function testProcessAllowsCorporationWikiAdministratorToUpdateMultipleGroupMemberships(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $operator = $this->principal($accountIdentifier);
        $member = $this->principal($accountIdentifier);
        $wikiAdministratorRole = $this->role('WIKI_ADMINISTRATOR');
        $administratorGroup = $this->principalGroup($accountIdentifier, 'Wiki Administrator');
        $administratorGroup->addRole($wikiAdministratorRole->roleIdentifier());
        $administratorGroup->addMember($operator->principalIdentifier());
        $targetGroup = $this->principalGroup($accountIdentifier, 'Target');

        /** @var PrincipalGroupRepositoryInterface&Mockery\MockInterface $principalGroupRepository */
        /** @var PrincipalGroupRepositoryInterface&Mockery\MockInterface $principalGroupRepository */
        /** @var PrincipalGroupRepositoryInterface&Mockery\MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByAccountId')->once()->with($accountIdentifier)->andReturn([$administratorGroup, $targetGroup]);
        $principalGroupRepository->shouldReceive('save')->once()->with(Mockery::on(static fn (PrincipalGroup $saved): bool => $saved->hasMember($member->principalIdentifier())));

        /** @var PrincipalRepositoryInterface&Mockery\MockInterface $principalRepository */
        /** @var PrincipalRepositoryInterface&Mockery\MockInterface $principalRepository */
        /** @var PrincipalRepositoryInterface&Mockery\MockInterface $principalRepository */
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->once()->with($operator->principalIdentifier())->andReturn($operator);
        $principalRepository->shouldReceive('findByIds')->once()->andReturn([(string) $member->principalIdentifier() => $member]);

        /** @var RoleRepositoryInterface&Mockery\MockInterface $roleRepository */
        /** @var RoleRepositoryInterface&Mockery\MockInterface $roleRepository */
        /** @var RoleRepositoryInterface&Mockery\MockInterface $roleRepository */
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByName')->once()->with('WIKI_ADMINISTRATOR')->andReturn($wikiAdministratorRole);

        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->once()
            ->with($operator, Action::PRINCIPAL_GROUP_MANAGE, Mockery::on(static fn (Resource $resource): bool => $resource->type() === ResourceType::PRINCIPAL_GROUP))
            ->andReturnTrue();

        $accountPrincipalRepository = $this->accountPrincipalRepositoryMock($accountIdentifier, $operator, $member);

        $output = new UpdatePrincipalGroupMembersOutput();
        (new UpdatePrincipalGroupMembers($principalGroupRepository, $principalRepository, $roleRepository, $policyEvaluator, $accountPrincipalRepository))->process(
            new UpdatePrincipalGroupMembersInput(
                $accountIdentifier,
                $operator->principalIdentifier(),
                [new PrincipalGroupMembers($targetGroup->principalGroupIdentifier(), [$member->principalIdentifier()])],
                AccountType::CORPORATION,
            ),
            $output,
        );

        $this->assertSame((string) $targetGroup->principalGroupIdentifier(), $output->toArray()['principalGroups'][0]['principalGroupIdentifier']);
    }

    public function testProcessRejectsIndividualAccount(): void
    {
        $this->expectException(AccountUpdateForbiddenException::class);

        (new UpdatePrincipalGroupMembers(
            self::principalGroupRepositoryMock(),
            self::principalRepositoryMock(),
            self::roleRepositoryMock(),
            self::policyEvaluatorMock(),
            self::accountPrincipalRepositoryEmptyMock(),
        ))->process(
            new UpdatePrincipalGroupMembersInput(
                new AccountIdentifier(StrTestHelper::generateUuid()),
                new PrincipalIdentifier(StrTestHelper::generateUuid()),
                [],
                AccountType::INDIVIDUAL,
            ),
            new UpdatePrincipalGroupMembersOutput(),
        );
    }

    public function testProcessRejectsRemovingLastWikiAdministrator(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $operator = $this->principal($accountIdentifier);
        $wikiAdministratorRole = $this->role('WIKI_ADMINISTRATOR');
        $administratorGroup = $this->principalGroup($accountIdentifier, 'Wiki Administrator');
        $administratorGroup->addRole($wikiAdministratorRole->roleIdentifier());
        $administratorGroup->addMember($operator->principalIdentifier());

        /** @var PrincipalGroupRepositoryInterface&Mockery\MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByAccountId')->once()->andReturn([$administratorGroup]);
        $principalGroupRepository->shouldNotReceive('save');

        /** @var PrincipalRepositoryInterface&Mockery\MockInterface $principalRepository */
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->once()->andReturn($operator);
        $principalRepository->shouldReceive('findByIds')->once()->andReturn([]);

        /** @var RoleRepositoryInterface&Mockery\MockInterface $roleRepository */
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByName')->once()->andReturn($wikiAdministratorRole);

        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturnTrue();

        $accountPrincipalRepository = $this->accountPrincipalRepositoryMock($accountIdentifier, $operator);

        $this->expectException(CannotRemoveLastWikiAdministratorException::class);

        (new UpdatePrincipalGroupMembers($principalGroupRepository, $principalRepository, $roleRepository, $policyEvaluator, $accountPrincipalRepository))->process(
            new UpdatePrincipalGroupMembersInput(
                $accountIdentifier,
                $operator->principalIdentifier(),
                [new PrincipalGroupMembers($administratorGroup->principalGroupIdentifier(), [])],
                AccountType::CORPORATION,
            ),
            new UpdatePrincipalGroupMembersOutput(),
        );
    }

    private static function principalGroupRepositoryMock(): PrincipalGroupRepositoryInterface
    {
        /** @var PrincipalGroupRepositoryInterface&Mockery\MockInterface $mock */
        $mock = Mockery::mock(PrincipalGroupRepositoryInterface::class);

        return $mock;
    }

    private static function principalRepositoryMock(): PrincipalRepositoryInterface
    {
        /** @var PrincipalRepositoryInterface&Mockery\MockInterface $mock */
        $mock = Mockery::mock(PrincipalRepositoryInterface::class);

        return $mock;
    }

    private static function roleRepositoryMock(): RoleRepositoryInterface
    {
        /** @var RoleRepositoryInterface&Mockery\MockInterface $mock */
        $mock = Mockery::mock(RoleRepositoryInterface::class);

        return $mock;
    }

    private static function policyEvaluatorMock(): PolicyEvaluatorInterface
    {
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $mock */
        $mock = Mockery::mock(PolicyEvaluatorInterface::class);

        return $mock;
    }

    private static function accountPrincipalRepositoryEmptyMock(): AccountPrincipalRepositoryInterface
    {
        /** @var AccountPrincipalRepositoryInterface&Mockery\MockInterface $mock */
        $mock = Mockery::mock(AccountPrincipalRepositoryInterface::class);

        return $mock;
    }

    private function accountPrincipalRepositoryMock(AccountIdentifier $accountIdentifier, Principal ...$principals): AccountPrincipalRepositoryInterface
    {
        /** @var AccountPrincipalRepositoryInterface&Mockery\MockInterface $mock */
        $mock = Mockery::mock(AccountPrincipalRepositoryInterface::class);

        foreach ($principals as $principal) {
            $mock->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')
                ->once()
                ->with(
                    $principal->identityIdentifier(),
                    $accountIdentifier,
                )
                ->andReturn(new AccountPrincipal(
                    new AccountPrincipalIdentifier(StrTestHelper::generateUuid()),
                    $principal->identityIdentifier(),
                    $accountIdentifier,
                ));
        }

        return $mock;
    }

    private function principal(AccountIdentifier $accountIdentifier): Principal
    {
        return new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()));
    }

    private function principalGroup(AccountIdentifier $accountIdentifier, string $name): PrincipalGroup
    {
        return new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), $accountIdentifier, $name, false, new DateTimeImmutable());
    }

    private function role(string $name): Role
    {
        return new Role(new RoleIdentifier(StrTestHelper::generateUuid()), $name, [], true, new DateTimeImmutable());
    }
}
