<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Application\Console\Commands\DemoteWikiCollaboratorCommand;
use Application\Console\Commands\PromoteWikiCollaboratorCommand;
use Database\Seeders\SystemPolicySeeder;
use Database\Seeders\SystemRoleSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Grading\Domain\Repository\PromotionHistoryRepositoryInterface;
use Source\Wiki\Grading\Domain\ValueObject\DemotionWarningIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateDemotionWarning;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\CreatePrincipalGroup;
use Tests\Helper\CreatePrincipalGroupMembership;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class ChangeWikiCollaboratorRoleCommandTest extends TestCase
{
    private const string EMAIL = 'collaborator@example.com';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        Artisan::registerCommand($this->app()->make(PromoteWikiCollaboratorCommand::class));
        Artisan::registerCommand($this->app()->make(DemoteWikiCollaboratorCommand::class));
    }

    public function testManualPromotionAndDemotionKeepBaseMembershipAndAreIdempotentWithoutNotifications(): void
    {
        $subject = $this->createSubject();
        CreateDemotionWarning::create(new DemotionWarningIdentifier(StrTestHelper::generateUuid()), $subject['principalIdentifier'], ['warning_count' => 2]);
        self::assertSame(0, $this->runCommand('promote'));
        $seniorGroup = DB::table('wiki_principal_groups')->where('name', 'Senior Collaborator')->value('id');
        self::assertIsString($seniorGroup);
        $this->assertDatabaseHas('wiki_principal_group_memberships', ['principal_group_id' => $seniorGroup, 'principal_id' => (string) $subject['principalIdentifier']]);
        $this->assertDatabaseMissing('demotion_warnings', ['principal_id' => (string) $subject['principalIdentifier']]);
        self::assertSame(0, $this->runCommand('promote'));
        self::assertSame(1, DB::table('promotion_histories')->count());
        CreateDemotionWarning::create(new DemotionWarningIdentifier(StrTestHelper::generateUuid()), $subject['principalIdentifier'], ['warning_count' => 2]);
        self::assertSame(0, $this->runCommand('demote'));
        self::assertSame(0, $this->runCommand('demote'));
        $this->assertDatabaseHas('wiki_principal_group_memberships', ['principal_group_id' => (string) $subject['defaultGroupIdentifier'], 'principal_id' => (string) $subject['principalIdentifier']]);
        $this->assertDatabaseMissing('wiki_principal_group_memberships', ['principal_group_id' => $seniorGroup, 'principal_id' => (string) $subject['principalIdentifier']]);
        $this->assertDatabaseMissing('demotion_warnings', ['principal_id' => (string) $subject['principalIdentifier']]);
        $this->assertDatabaseHas('promotion_histories', ['principal_id' => (string) $subject['principalIdentifier'], 'from_role' => 'Collaborator', 'to_role' => 'SeniorCollaborator', 'reason' => 'Manually promoted by console command']);
        $this->assertDatabaseHas('promotion_histories', ['principal_id' => (string) $subject['principalIdentifier'], 'from_role' => 'SeniorCollaborator', 'to_role' => 'Collaborator', 'reason' => 'Manually demoted by console command']);
        self::assertSame(2, DB::table('promotion_histories')->count());
        self::assertSame(0, DB::table('contribution_point_histories')->count());
    }

    public function testReusesSeniorGroupByRoleAndPreservesOtherMembersAndAccounts(): void
    {
        $subject = $this->createSubject();
        $seniorGroup = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        CreatePrincipalGroup::create($seniorGroup, $subject['accountIdentifier'], ['name' => 'Renamed senior group']);
        $this->attachRole($seniorGroup, 'SeniorCollaborator');
        $otherIdentity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $otherPrincipal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($otherIdentity);
        CreatePrincipal::create($otherPrincipal, $otherIdentity, $subject['accountIdentifier']);
        CreatePrincipalGroupMembership::create((string) $seniorGroup, (string) $otherPrincipal);
        $otherAccount = new AccountIdentifier(StrTestHelper::generateUuid());
        $sameIdentityPrincipal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $otherAccount);
        CreatePrincipal::create($sameIdentityPrincipal, $subject['identityIdentifier'], $otherAccount);
        self::assertSame(0, $this->runCommand('promote'));
        self::assertSame(2, DB::table('wiki_principal_groups')->count());
        $this->assertDatabaseHas('wiki_principal_group_memberships', ['principal_group_id' => (string) $seniorGroup, 'principal_id' => (string) $subject['principalIdentifier']]);
        $this->assertDatabaseHas('wiki_principal_group_memberships', ['principal_group_id' => (string) $seniorGroup, 'principal_id' => (string) $otherPrincipal]);
        $this->assertDatabaseMissing('wiki_principal_group_memberships', ['principal_id' => (string) $sameIdentityPrincipal]);
    }

    public function testCommandRollsBackMembershipAndWarningsWhenHistorySaveFails(): void
    {
        $subject = $this->createSubject();
        CreateDemotionWarning::create(new DemotionWarningIdentifier(StrTestHelper::generateUuid()), $subject['principalIdentifier']);
        $promotionHistoryRepository = Mockery::mock(PromotionHistoryRepositoryInterface::class);
        $promotionHistoryRepository->shouldReceive('save')->once()->andThrow(new RuntimeException('history unavailable'));
        $this->app()->instance(PromotionHistoryRepositoryInterface::class, $promotionHistoryRepository);
        self::assertSame(1, $this->runCommand('promote'));
        self::assertSame(1, DB::table('wiki_principal_groups')->count());
        self::assertSame(1, DB::table('wiki_principal_group_memberships')->count());
        $this->assertDatabaseHas('demotion_warnings', ['principal_id' => (string) $subject['principalIdentifier']]);
        self::assertSame(0, DB::table('promotion_histories')->count());
    }

    /** @return array<string, array{string}> */
    public static function missingTargets(): array
    {
        return ['identity' => ['identities'], 'account' => ['accounts'], 'principal' => ['wiki_principals'], 'base membership' => ['wiki_principal_group_memberships'], 'system role' => ['wiki_roles']];
    }

    #[DataProvider('missingTargets')]
    public function testCommandFailsForMissingTargetWithoutWritingHistory(string $table): void
    {
        $this->createSubject();
        if ($table === 'wiki_roles') {
            DB::table($table)->where('name', 'SeniorCollaborator')->delete();
        } else {
            DB::table($table)->delete();
        }
        self::assertSame(1, $this->runCommand('promote'));
        self::assertSame(0, DB::table('promotion_histories')->count());
    }

    public function testInvalidEmailFailsBeforeChangingMembership(): void
    {
        $this->createSubject();
        self::assertSame(1, Artisan::call('wiki:collaborator:promote', ['email' => 'invalid'], new BufferedOutput()));
        self::assertSame(1, Artisan::call('wiki:collaborator:demote', ['email' => 'invalid'], new BufferedOutput()));
        self::assertSame(1, DB::table('wiki_principal_group_memberships')->count());
        self::assertSame(0, DB::table('promotion_histories')->count());
    }

    /**
     * @return array{accountIdentifier: AccountIdentifier, identityIdentifier: IdentityIdentifier, principalIdentifier: PrincipalIdentifier, defaultGroupIdentifier: PrincipalGroupIdentifier}
     */
    private function createSubject(): array
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $defaultGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier, ['email' => self::EMAIL]);
        CreateIdentity::create($identityIdentifier, ['email' => self::EMAIL]);
        CreatePrincipal::create($principalIdentifier, $identityIdentifier, $accountIdentifier);
        CreatePrincipalGroup::create($defaultGroupIdentifier, $accountIdentifier, ['is_default' => true]);
        $this->attachRole($defaultGroupIdentifier, 'Collaborator');
        CreatePrincipalGroupMembership::create((string) $defaultGroupIdentifier, (string) $principalIdentifier);
        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldNotReceive('dispatch');
        $this->app()->instance(EventDispatcherInterface::class, $eventDispatcher);

        return compact('accountIdentifier', 'identityIdentifier', 'principalIdentifier', 'defaultGroupIdentifier');
    }

    /** @phpstan-impure */
    private function runCommand(string $action): int
    {
        return Artisan::call('wiki:collaborator:' . $action, ['email' => self::EMAIL], new BufferedOutput());
    }

    private function attachRole(PrincipalGroupIdentifier $groupIdentifier, string $name): void
    {
        $roleId = DB::table('wiki_roles')->whereNull('account_id')->where('name', $name)->value('id');
        self::assertIsString($roleId);
        DB::table('wiki_principal_group_role_attachments')->insert(['principal_group_id' => (string) $groupIdentifier, 'role_id' => $roleId]);
    }
}
