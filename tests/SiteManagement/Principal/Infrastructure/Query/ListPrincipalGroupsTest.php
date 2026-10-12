<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Infrastructure\Query;

use Database\Seeders\SiteManagementAuthorizationSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\SiteManagement\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInput;
use Source\SiteManagement\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Tests\Helper\SiteManagementPrincipalGroupsFixture;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class ListPrincipalGroupsTest extends TestCase
{
    public function testAccountSnapshotIncludesEmptyGroupsAndUnassignedUsersAndExcludesMixedOperatorGroups(): void
    {
        $f = new SiteManagementPrincipalGroupsFixture();
        $f->group($f->otherAccount, [], [$f->foreign]);
        $result = $this->app()->make(ListPrincipalGroupsInterface::class)->process(new ListPrincipalGroupsInput($f->account, $f->administrator->principalIdentifier()));
        self::assertCount(2, $result->principalGroups());
        self::assertCount(3, $result->principals());
        self::assertEqualsCanonicalizing([(string)$f->administrators->principalGroupIdentifier(),(string)$f->general->principalGroupIdentifier()], array_map(static fn ($group): string => $group->principalGroupIdentifier(), $result->principalGroups()));
        $alice = array_values(array_filter($result->principals(), static fn ($p): bool => $p->principalIdentifier() === (string)$f->administrator->principalIdentifier()))[0];
        self::assertSame('Alice', $alice->identityName());
        self::assertSame((string)$f->administrator->identityIdentifier().'@example.com', $alice->email());
        foreach ($result->principalGroups() as $group) {
            if ($group->principalGroupIdentifier() === (string)$f->general->principalGroupIdentifier()) {
                self::assertSame([], $group->members());
            }
        }
    }

    public function testSnapshotUsesIdentifiersToBreakEqualCreationTimes(): void
    {
        $f = new SiteManagementPrincipalGroupsFixture();
        DB::table('site_management_principal_groups')->where('account_id', (string) $f->account)->update(['created_at' => '2026-01-01 00:00:00']);
        DB::table('site_management_principals')->where('account_id', (string) $f->account)->update(['created_at' => '2026-01-01 00:00:00']);
        $result = $this->app()->make(ListPrincipalGroupsInterface::class)->process(new ListPrincipalGroupsInput($f->account, $f->administrator->principalIdentifier()));
        $groupIds = array_map(static fn ($group): string => $group->principalGroupIdentifier(), $result->principalGroups());
        $principalIds = array_map(static fn ($principal): string => $principal->principalIdentifier(), $result->principals());
        $expectedGroups = $groupIds;
        $expectedPrincipals = $principalIds;
        sort($expectedGroups);
        sort($expectedPrincipals);
        self::assertSame($expectedGroups, $groupIds);
        self::assertSame($expectedPrincipals, $principalIds);
        foreach ($result->principalGroups() as $group) {
            $members = array_map(static fn ($member): string => $member->principalIdentifier(), $group->members());
            $expected = $members;
            sort($expected);
            self::assertSame($expected, $members);
            $roles = $group->roleIdentifiers();
            $expectedRoles = $roles;
            sort($expectedRoles);
            self::assertSame($expectedRoles, $roles);
        }
    }

    /** @return array<string,array{string}> */
    public static function deniedCases(): array
    {
        return ['general' => ['general'],'operator' => ['operator'],'foreign' => ['foreign'],'missing' => ['missing'],'explicit deny' => ['deny']];
    }

    #[DataProvider('deniedCases')]
    public function testManagementRequiresSameAccountAllow(string $case): void
    {
        $f = new SiteManagementPrincipalGroupsFixture();
        $principal = $f->administrator->principalIdentifier();
        if ($case === 'general') {
            $f->group($f->account, [SiteManagementAuthorizationSeeder::GENERAL_ROLE], [$f->unassigned]);
            $principal = $f->unassigned->principalIdentifier();
        }
        if ($case === 'operator') {
            $f->group($f->account, [SiteManagementAuthorizationSeeder::OPERATOR_ROLE], [$f->unassigned]);
            $principal = $f->unassigned->principalIdentifier();
        }
        if ($case === 'foreign') {
            $principal = $f->foreign->principalIdentifier();
        }
        if ($case === 'missing') {
            $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        }
        if ($case === 'deny') {
            DB::table('site_management_policies')->where('id', SiteManagementAuthorizationSeeder::GENERAL_ROLE)->update(['statements' => json_encode([['effect' => 'deny','actions' => ['principal-group:manage'],'resource_types' => ['principal_group'],'condition' => null]], JSON_THROW_ON_ERROR)]);
            $f->group($f->account, [SiteManagementAuthorizationSeeder::GENERAL_ROLE], [$f->administrator]);
        }
        $this->expectException(UnauthorizedException::class);
        $this->app()->make(ListPrincipalGroupsInterface::class)->process(new ListPrincipalGroupsInput($f->account, $principal));
    }
}
