<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Database\Seeders\SiteManagementAuthorizationSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityInput;
use Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityInterface;
use Source\SiteManagement\Principal\Application\Exception\CannotRemoveLastAdministratorException;
use Source\SiteManagement\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\SiteManagement\Principal\Application\Exception\PrincipalNotFoundException;
use Source\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\PrincipalGroupMembers;
use Source\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersInterface;
use Source\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersOutput;
use Source\SiteManagement\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInput;
use Source\SiteManagement\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInterface;
use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Principal\Infrastructure\Repository\PrincipalGroupRepository;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Tests\Helper\SiteManagementPrincipalGroupsFixture;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class UpdatePrincipalGroupMembersTest extends TestCase
{
    /** @param array<int, PrincipalGroupMembers> $groups
     * @throws CannotRemoveLastAdministratorException
     * @throws PrincipalGroupNotFoundException
     * @throws PrincipalNotFoundException
     * @throws UnauthorizedException */
    private function update(SiteManagementPrincipalGroupsFixture $f, array $groups): UpdatePrincipalGroupMembersOutput
    {
        $output = new UpdatePrincipalGroupMembersOutput();
        DB::transaction(function () use ($f, $groups, $output): void {
            $this->app()->make(UpdatePrincipalGroupMembersInterface::class)->process(new UpdatePrincipalGroupMembersInput($f->account, $f->administrator->principalIdentifier(), $groups), $output);
        });

        return $output;
    }

    public function testMultipleReplacementAndSelfRemovalReturnsUpdatedGroupsAndNextRequestUsesNewPolicies(): void
    {
        $f = new SiteManagementPrincipalGroupsFixture();
        // Remove the administrator role from the protected group so it cannot retain self permission.
        DB::table('site_management_principal_group_role_attachments')->where('principal_group_id', (string)$f->protected->principalGroupIdentifier())->where('role_id', SiteManagementAuthorizationSeeder::ADMINISTRATOR_ROLE)->delete();
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('set')->andReturn(true);
        $query = $this->app()->make(GetAuthenticatedIdentityInterface::class);
        $input = new GetAuthenticatedIdentityInput($f->administrator->identityIdentifier());
        $before = $query->process($input);
        self::assertContains('principal_group_management', array_column($before->siteManagementPolicies(), 'name'));
        $output = $this->update($f, [new PrincipalGroupMembers($f->administrators->principalGroupIdentifier(), [$f->otherAdministrator->principalIdentifier()]),new PrincipalGroupMembers($f->general->principalGroupIdentifier(), [$f->unassigned->principalIdentifier()])]);
        $result = $output->toArray();
        self::assertCount(2, $result['principalGroups']);
        self::assertSame([1, 1], array_column($result['principalGroups'], 'memberCount'));
        self::assertArrayNotHasKey('principals', $result);
        $after = $query->process($input);
        self::assertNotContains('principal_group_management', array_column($after->siteManagementPolicies(), 'name'));
        $this->assertDatabaseMissing('site_management_principal_group_memberships', ['principal_group_id' => (string)$f->administrators->principalGroupIdentifier(),'principal_id' => (string)$f->administrator->principalIdentifier()]);
        $this->expectException(UnauthorizedException::class);
        $this->app()->make(ListPrincipalGroupsInterface::class)->process(new ListPrincipalGroupsInput($f->account, $f->administrator->principalIdentifier()));
    }

    public function testOmittedAdministratorGroupRetainsMembersAndAllowsReassigningUnassignedUserToMultipleGroups(): void
    {
        $f = new SiteManagementPrincipalGroupsFixture();
        $another = $f->group($f->account, [], []);
        $output = $this->update($f, [new PrincipalGroupMembers($f->general->principalGroupIdentifier(), [$f->unassigned->principalIdentifier()]),new PrincipalGroupMembers($another->principalGroupIdentifier(), [$f->unassigned->principalIdentifier()])]);
        self::assertCount(2, $output->toArray()['principalGroups']);
        self::assertNotContains((string) $f->administrators->principalGroupIdentifier(), array_column($output->toArray()['principalGroups'], 'principalGroupIdentifier'));
        $this->assertDatabaseHas('site_management_principal_group_memberships', ['principal_group_id' => (string)$f->administrators->principalGroupIdentifier(),'principal_id' => (string)$f->administrator->principalIdentifier()]);
        self::assertSame(2, DB::table('site_management_principal_group_memberships')->where('principal_id', (string)$f->unassigned->principalIdentifier())->count());
    }

    public function testAdministratorInOmittedSecondGroupAllowsClearingFirstGroup(): void
    {
        $f = new SiteManagementPrincipalGroupsFixture();
        $f->group($f->account, [SiteManagementAuthorizationSeeder::ADMINISTRATOR_ROLE], [$f->otherAdministrator]);
        $output = $this->update($f, [new PrincipalGroupMembers($f->administrators->principalGroupIdentifier(), [])]);
        self::assertCount(1, $output->toArray()['principalGroups']);
        self::assertSame(0, $output->toArray()['principalGroups'][0]['memberCount']);
        $this->assertDatabaseMissing('site_management_principal_group_memberships', ['principal_group_id' => (string)$f->administrators->principalGroupIdentifier()]);
    }

    public function testEmptyGroupUpdatesReturnNoGroupsWithoutChangingMemberships(): void
    {
        $f = new SiteManagementPrincipalGroupsFixture();
        $before = DB::table('site_management_principal_group_memberships')->orderBy('principal_group_id')->orderBy('principal_id')->get()->toArray();
        $output = $this->update($f, []);
        self::assertSame(['principalGroups' => []], $output->toArray());
        self::assertEquals($before, DB::table('site_management_principal_group_memberships')->orderBy('principal_group_id')->orderBy('principal_id')->get()->toArray());
    }

    /** @return array<string,array{string,class-string}> */
    public static function rejectedCases(): array
    {
        return [
         'last admin' => ['last',CannotRemoveLastAdministratorException::class],
         'foreign admin does not count' => ['foreign-admin',CannotRemoveLastAdministratorException::class],
         'missing admin does not count' => ['missing-admin',CannotRemoveLastAdministratorException::class],
         'protected mixed group clear' => ['protected-clear',UnauthorizedException::class],
         'protected mixed group add' => ['protected-add',UnauthorizedException::class],
         'protected mixed group replace' => ['protected-replace',UnauthorizedException::class],
         'protected plain Operator' => ['operator-only',UnauthorizedException::class],
         'missing group' => ['missing-group',PrincipalGroupNotFoundException::class],
         'foreign group' => ['foreign-group',PrincipalGroupNotFoundException::class],
         'missing principal' => ['missing-principal',PrincipalNotFoundException::class],
         'foreign principal' => ['foreign-principal',PrincipalNotFoundException::class],
         'duplicate groups' => ['duplicate-group',InvalidArgumentException::class],
         'duplicate members' => ['duplicate-member',InvalidArgumentException::class],
         'general caller' => ['general-caller',UnauthorizedException::class],
         'operator caller' => ['operator-caller',UnauthorizedException::class],
         'missing caller' => ['missing-caller',UnauthorizedException::class],
         'foreign caller' => ['foreign-caller',UnauthorizedException::class],
         'explicit deny' => ['deny',UnauthorizedException::class],
        ];
    }

    /** @param class-string $exception */
    #[DataProvider('rejectedCases')]
    public function testRejectsBeforeAnySave(string $case, string $exception): void
    {
        $f = new SiteManagementPrincipalGroupsFixture();
        $groups = [new PrincipalGroupMembers($f->general->principalGroupIdentifier(), [$f->unassigned->principalIdentifier()])];
        if ($case === 'last') {
            $groups[] = new PrincipalGroupMembers($f->administrators->principalGroupIdentifier(), []);
        }
        if ($case === 'foreign-admin' || $case === 'missing-admin') {
            $f->group($f->account, [SiteManagementAuthorizationSeeder::ADMINISTRATOR_ROLE], [$f->foreign]);
            if ($case === 'missing-admin') {
                $repo = Mockery::mock(PrincipalRepositoryInterface::class);
                $repo->shouldReceive('findById')->andReturn($f->administrator);
                $repo->shouldReceive('findByIds')->andReturn([(string)$f->unassigned->principalIdentifier() => $f->unassigned]);
                $this->app()->instance(PrincipalRepositoryInterface::class, $repo);
            }
            $groups[] = new PrincipalGroupMembers($f->administrators->principalGroupIdentifier(), []);
        }
        if (str_starts_with($case, 'protected') || $case === 'operator-only') {
            if ($case === 'operator-only') {
                DB::table('site_management_principal_group_role_attachments')->where('principal_group_id', (string)$f->protected->principalGroupIdentifier())->where('role_id', SiteManagementAuthorizationSeeder::ADMINISTRATOR_ROLE)->delete();
            }
            $members = match($case) {
                'protected-clear' => [],'protected-add' => [$f->administrator->principalIdentifier(),$f->unassigned->principalIdentifier()],default => [$f->otherAdministrator->principalIdentifier()]
            };
            $groups[] = new PrincipalGroupMembers($f->protected->principalGroupIdentifier(), $members);
        }
        if ($case === 'missing-group') {
            $groups[] = new PrincipalGroupMembers(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), []);
        }
        if ($case === 'foreign-group') {
            $g = $f->group($f->otherAccount, [], []);
            $groups[] = new PrincipalGroupMembers($g->principalGroupIdentifier(), []);
        }
        if ($case === 'missing-principal') {
            $groups[] = new PrincipalGroupMembers($f->administrators->principalGroupIdentifier(), [new PrincipalIdentifier(StrTestHelper::generateUuid())]);
        }
        if ($case === 'foreign-principal') {
            $groups[] = new PrincipalGroupMembers($f->administrators->principalGroupIdentifier(), [$f->foreign->principalIdentifier()]);
        }
        if ($case === 'duplicate-group') {
            $groups[] = $groups[0];
        }
        if ($case === 'duplicate-member') {
            $groups = [new PrincipalGroupMembers($f->general->principalGroupIdentifier(), [$f->unassigned->principalIdentifier(),$f->unassigned->principalIdentifier()])];
        }
        if ($case === 'operator-caller') {
            $f->group($f->account, [SiteManagementAuthorizationSeeder::OPERATOR_ROLE], [$f->unassigned]);
        }
        if ($case === 'general-caller') {
            $f->administrators->replaceMembers([$f->otherAdministrator->principalIdentifier()]);
            (new PrincipalGroupRepository())->save($f->administrators);
            DB::table('site_management_principal_group_role_attachments')->where('principal_group_id', (string)$f->protected->principalGroupIdentifier())->where('role_id', SiteManagementAuthorizationSeeder::ADMINISTRATOR_ROLE)->delete();
        }
        if ($case === 'deny') {
            DB::table('site_management_policies')->where('id', SiteManagementAuthorizationSeeder::GENERAL_ROLE)->update(['statements' => json_encode([['effect' => 'deny', 'actions' => ['principal-group:manage'], 'resource_types' => ['principal_group'], 'condition' => null]], JSON_THROW_ON_ERROR)]);
            $f->group($f->account, [SiteManagementAuthorizationSeeder::GENERAL_ROLE], [$f->administrator]);
        }
        $realRepository = new PrincipalGroupRepository();
        $validationRepository = $this->createMock(PrincipalGroupRepositoryInterface::class);
        $validationRepository->method('findByAccountId')->willReturnCallback($realRepository->findByAccountId(...));
        $validationRepository->method('findByPrincipalId')->willReturnCallback($realRepository->findByPrincipalId(...));
        $validationRepository->expects(self::never())->method('save');
        $this->app()->instance(PrincipalGroupRepositoryInterface::class, $validationRepository);
        $before = DB::table('site_management_principal_group_memberships')->orderBy('principal_group_id')->orderBy('principal_id')->get()->toArray();

        try {
            $output = new UpdatePrincipalGroupMembersOutput();
            $callerIdentifier = match ($case) {
                'operator-caller' => $f->unassigned->principalIdentifier(),
                'foreign-caller' => $f->foreign->principalIdentifier(),
                'missing-caller' => new PrincipalIdentifier(StrTestHelper::generateUuid()),
                default => $f->administrator->principalIdentifier(),
            };
            $this->app()->make(UpdatePrincipalGroupMembersInterface::class)->process(new UpdatePrincipalGroupMembersInput($f->account, $callerIdentifier, $groups), $output);
            self::fail('Expected rejection.');
        } catch (CannotRemoveLastAdministratorException|UnauthorizedException|PrincipalGroupNotFoundException|PrincipalNotFoundException|InvalidArgumentException $e) {
            self::assertInstanceOf($exception, $e);
        }
        self::assertEquals($before, DB::table('site_management_principal_group_memberships')->orderBy('principal_group_id')->orderBy('principal_id')->get()->toArray());
    }

    public function testTransactionRollsBackAllSavesWhenLaterSaveFails(): void
    {
        $f = new SiteManagementPrincipalGroupsFixture();
        $another = $f->group($f->account, [], []);
        $real = new PrincipalGroupRepository();
        $repo = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $repo->shouldReceive('findByAccountId')->andReturnUsing($real->findByAccountId(...));
        $repo->shouldReceive('findByPrincipalId')->andReturnUsing($real->findByPrincipalId(...));
        $count = 0;
        $repo->shouldReceive('save')->andReturnUsing(static function (PrincipalGroup $group) use ($real, &$count): void {
            $real->save($group);
            if (++$count === 2) {
                throw new RuntimeException('second save failed');
            }
        });
        $this->app()->instance(PrincipalGroupRepositoryInterface::class, $repo);
        $before = DB::table('site_management_principal_group_memberships')->orderBy('principal_group_id')->orderBy('principal_id')->get()->toArray();

        try {
            $this->update($f, [new PrincipalGroupMembers($f->general->principalGroupIdentifier(), [$f->unassigned->principalIdentifier()]),new PrincipalGroupMembers($another->principalGroupIdentifier(), [$f->unassigned->principalIdentifier()])]);
            self::fail('Expected failure.');
        } catch (RuntimeException $e) {
            self::assertSame('second save failed', $e->getMessage());
        }
        self::assertEquals($before, DB::table('site_management_principal_group_memberships')->orderBy('principal_group_id')->orderBy('principal_id')->get()->toArray());
    }
}
