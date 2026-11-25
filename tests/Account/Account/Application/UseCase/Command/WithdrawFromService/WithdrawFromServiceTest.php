<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\WithdrawFromService;

use Application\Http\Action\Identity\Query\GetWithdrawalEligibility\GetWithdrawalEligibilityAction;
use Application\Http\Context\ActorContext;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\NullLogger;
use RuntimeException;
use Source\Account\Account\Application\Service\AccountSnapshotDocumentServiceInterface;
use Source\Account\Account\Application\Service\WithdrawalEligibilityServiceInterface;
use Source\Account\Account\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInput;
use Source\Account\Account\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInterface;
use Source\Account\Account\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceOutput;
use Source\Account\Account\Domain\Exception\IdentityWithdrawalNotAllowedException;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateAccountSnapshot;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class WithdrawFromServiceTest extends TestCase
{
    /** @return array<string, array{bool}> */
    public static function storageFailures(): array
    {
        return ['available' => [false], 'unavailable' => [true]];
    }

    #[DataProvider('storageFailures')]
    public function testAllHistoryIsDeletedWhileCorporateHistoryIsRetained(bool $failStorage): void
    {
        $disk = Storage::fake('verification-documents');
        $allow = $this->createStub(PolicyEvaluatorInterface::class);
        $allow->method('evaluate')->willReturn(true);
        $this->app()->instance(PolicyEvaluatorInterface::class, $allow);
        $identity = $this->createIdentityArchive();
        [$individual] = $this->createMembership($identity, 'individual');
        [$corporation] = $this->createMembership($identity, 'corporation');
        $individualPaths = $this->createDocumentHistory($individual);
        $corporatePaths = $this->createDocumentHistory($corporation);
        if ($failStorage) {
            $unavailable = $this->createMock(Filesystem::class);
            $unavailable->expects(self::exactly(count($individualPaths)))->method('delete')->willThrowException(new RuntimeException('Storage unavailable'));
            Storage::shouldReceive('disk')->with('verification-documents')->andReturn(...[...array_fill(0, count($individualPaths), $unavailable), $disk]);
        }

        try {
            $this->app()->make(WithdrawFromServiceInterface::class)->process(new WithdrawFromServiceInput($identity), new WithdrawFromServiceOutput());
            foreach (['account_snapshots', 'account_documents', 'account_category_change_requests', 'account_snapshot_change_requests'] as $table) {
                $this->assertDatabaseMissing($table, ['account_id' => $individual]);
                $this->assertDatabaseHas($table, ['account_id' => $corporation]);
            }
            $this->assertDatabaseCount('account_snapshot_documents', 8);
            foreach ($individualPaths as $path) {
                $disk->assertExists($path);
            }
            DB::commit();
            foreach ($individualPaths as $path) {
                if ($failStorage) {
                    $disk->assertExists($path);
                } else {
                    $disk->assertMissing($path);
                }
            }
            foreach ($corporatePaths as $path) {
                $disk->assertExists($path);
            }
        } finally {
            if (DB::transactionLevel() === 0) {
                DB::table('accounts')->where('id', $corporation)->delete();
                DB::table('archived_accounts')->where('account_id', $individual)->delete();
                DB::table('archived_principals')->where('identity_id', (string) $identity)->delete();
                DB::table('archived_identities')->where('identity_id', (string) $identity)->delete();
                DB::table('identities')->where('id', (string) $identity)->delete();
                DB::beginTransaction();
            }
        }
    }

    /** @return list<string> */
    private function createDocumentHistory(string $account): array
    {
        DB::table('accounts')->where('id', $account)->update(['phone' => '+819012345678', 'address_country_code' => 'FR', 'address_postal_code' => '75001', 'address_line1' => 'Paris']);
        $types = DB::table('accounts')->where('id', $account)->value('type') === 'individual' ? ['passport', 'selfie'] : ['business_registration', 'representative_id'];
        $paths = [];
        foreach ($types as $type) {
            $path = $account . '/' . $type . '.pdf';
            Storage::disk('verification-documents')->put($path, 'private document');
            DB::table('account_documents')->insert(['account_id' => $account, 'document_type' => $type, 'document_path' => $path, 'uploaded_at' => now()]);
            $paths[] = $path;
        }
        $snapshots = [];
        foreach (range(1, 4) as $number) {
            DB::table('accounts')->where('id', $account)->update(['name' => 'Historical name ' . $number]);
            $snapshot = CreateAccountSnapshot::save(new AccountIdentifier($account));
            $snapshots[] = (string) $snapshot->snapshotIdentifier();
            foreach ($snapshot->documents() as $document) {
                $paths[] = (string) $document->documentPath();
            }
        }
        DB::table('account_snapshots')->whereIn('id', [$snapshots[2], $snapshots[3]])->update(['status' => 'rejected']);
        foreach (['pending', 'approved', 'rejected'] as $index => $status) {
            DB::table('account_snapshot_change_requests')->insert([
                'id' => StrTestHelper::generateUuid(), 'account_id' => $account,
                'snapshot_id' => $snapshots[0], 'reviewed_snapshot_id' => $status === 'pending' ? null : $snapshots[$index],
                'change_reason' => 'Private change reason', 'status' => $status, 'requested_at' => now(),
                'reviewed_by' => $status === 'pending' ? null : StrTestHelper::generateUuid(),
                'reviewed_at' => $status === 'pending' ? null : now(),
                'rejection_reason' => $status === 'rejected' ? json_encode(['code' => 'other', 'detail' => 'Private review']) : null,
            ]);
            DB::table('account_category_change_requests')->insert([
                'id' => StrTestHelper::generateUuid(), 'account_id' => $account,
                'snapshot_id' => $snapshots[0], 'approved_snapshot_id' => $status === 'approved' ? $snapshots[1] : null,
                'current_account_category' => 'general', 'requested_account_category' => 'talent',
                'status' => $status, 'requested_at' => now(),
            ]);
        }

        return $paths;
    }

    public function testRollbackRestoresDeletedHistoryAndCancelsFileDeletion(): void
    {
        $disk = Storage::fake('verification-documents');
        $allow = $this->createStub(PolicyEvaluatorInterface::class);
        $allow->method('evaluate')->willReturn(true);
        $this->app()->instance(PolicyEvaluatorInterface::class, $allow);
        $identity = $this->createIdentityArchive();
        [$account] = $this->createMembership($identity, 'individual');
        $paths = $this->createDocumentHistory($account);
        $failure = new RuntimeException('Failure after account deletion');

        try {
            DB::transaction(function () use ($identity, $account, $failure): void {
                $this->app()->make(WithdrawFromServiceInterface::class)->process(new WithdrawFromServiceInput($identity), new WithdrawFromServiceOutput());
                $this->assertDatabaseMissing('accounts', ['id' => $account]);
                $this->assertDatabaseCount('account_snapshot_change_requests', 0);

                throw $failure;
            });
        } catch (RuntimeException $exception) {
            self::assertSame($failure, $exception);
        }
        $this->assertDatabaseHas('accounts', ['id' => $account]);
        $this->assertDatabaseCount('account_snapshots', 4);
        $this->assertDatabaseCount('account_snapshot_documents', 8);
        $this->assertDatabaseCount('account_category_change_requests', 3);
        $this->assertDatabaseCount('account_snapshot_change_requests', 3);
        foreach ($paths as $path) {
            $disk->assertExists($path);
        }
    }

    public function testIneligibleWithdrawalRetainsAllHistoryAndFiles(): void
    {
        $disk = Storage::fake('verification-documents');
        $allow = $this->createStub(PolicyEvaluatorInterface::class);
        $allow->method('evaluate')->willReturn(true);
        $this->app()->instance(PolicyEvaluatorInterface::class, $allow);
        $identity = $this->createIdentityArchive();
        [$account] = $this->createMembership($identity, 'individual');
        $paths = $this->createDocumentHistory($account);
        $this->createMembership($identity, 'corporation', 'agency');

        try {
            $this->app()->make(WithdrawFromServiceInterface::class)->process(new WithdrawFromServiceInput($identity), new WithdrawFromServiceOutput());
            self::fail('Ineligible withdrawal must fail before deletion.');
        } catch (IdentityWithdrawalNotAllowedException) {
            $this->assertDatabaseCount('account_snapshots', 4);
            $this->assertDatabaseCount('account_snapshot_documents', 8);
            $this->assertDatabaseCount('account_category_change_requests', 3);
            $this->assertDatabaseCount('account_snapshot_change_requests', 3);
            $this->assertDatabaseCount('account_documents', 2);
            foreach ($paths as $path) {
                $disk->assertExists($path);
            }
        }
    }

    public function testDeletesSnapshotChangeRequestsWhenWithdrawing(): void
    {
        $identity = $this->createIdentityArchive();
        [$account] = $this->createMembership($identity, 'individual');
        foreach (['pending', 'approved', 'rejected'] as $status) {
            DB::table('account_snapshot_change_requests')->insert([
                'id' => StrTestHelper::generateUuid(),
                'account_id' => $account,
                'snapshot_id' => StrTestHelper::generateUuid(),
                'change_reason' => 'Private reason',
                'status' => $status,
                'requested_at' => now(),
            ]);
        }

        $this->app()->make(WithdrawFromServiceInterface::class)->process(new WithdrawFromServiceInput($identity), new WithdrawFromServiceOutput());

        $this->assertDatabaseMissing('account_snapshot_change_requests', ['account_id' => $account]);
    }

    public function testArchivesAllPrincipalsAndOnlyDeletesIndividualAccounts(): void
    {
        $identityIdentifier = $this->createIdentityArchive();
        [$individual, $individualPrincipal] = $this->createMembership($identityIdentifier, 'individual');
        [$corporation, $corporatePrincipal] = $this->createMembership($identityIdentifier, 'corporation');
        $this->assertReadEligibility($identityIdentifier, true);
        $snapshotService = Mockery::mock(AccountSnapshotDocumentServiceInterface::class);
        $snapshotService->shouldReceive('deleteForAccount')->once()->with(Mockery::on(static fn (AccountIdentifier $id): bool => (string) $id === $individual));
        $this->app()->instance(AccountSnapshotDocumentServiceInterface::class, $snapshotService);

        $this->app()->make(WithdrawFromServiceInterface::class)->process(new WithdrawFromServiceInput($identityIdentifier), new WithdrawFromServiceOutput());

        $this->assertDatabaseMissing('accounts', ['id' => $individual]);
        $this->assertDatabaseHas('accounts', ['id' => $corporation]);
        $this->assertDatabaseHas('archived_accounts', ['account_id' => $individual, 'account_category' => 'general', 'account_type' => 'individual']);
        $this->assertDatabaseMissing('archived_accounts', ['account_id' => $corporation]);
        foreach ([$individualPrincipal => $individual, $corporatePrincipal => $corporation] as $principal => $account) {
            $this->assertDatabaseHas('archived_principals', ['identity_id' => (string) $identityIdentifier, 'principal_type' => 'account', 'principal_id' => $principal, 'account_id' => $account]);
            $this->assertDatabaseMissing('account_principals', ['id' => $principal]);
        }
    }

    /** @return array<string, array{?string, string}> */
    public static function disallowedMemberships(): array
    {
        return [
            'agency' => ['corporation', 'agency'],
            'talent' => ['individual', 'talent'],
            'unclassified account' => [null, 'general'],
        ];
    }

    #[DataProvider('disallowedMemberships')]
    public function testChecksEveryMembershipBeforeAnyArchiveOrDeletion(?string $type, string $category): void
    {
        $identityIdentifier = $this->createIdentityArchive();
        [$individual] = $this->createMembership($identityIdentifier, 'individual');
        [$disallowedAccount] = $this->createMembership($identityIdentifier, $type, $category);
        $this->assertReadEligibility($identityIdentifier, false);

        try {
            $this->app()->make(WithdrawFromServiceInterface::class)->process(new WithdrawFromServiceInput($identityIdentifier), new WithdrawFromServiceOutput());
            $this->fail('Ineligible membership must reject withdrawal.');
        } catch (IdentityWithdrawalNotAllowedException) {
            $this->assertDatabaseHas('accounts', ['id' => $individual]);
            $this->assertDatabaseHas('accounts', ['id' => $disallowedAccount]);
            $this->assertDatabaseCount('archived_accounts', 0);
            $this->assertDatabaseCount('archived_principals', 0);
        }
    }

    public function testCorporateSystemOwnerIsRejectedEvenWithAnotherOwner(): void
    {
        $identityIdentifier = $this->createIdentityArchive();
        [$account, $principal] = $this->createMembership($identityIdentifier, 'corporation');
        $otherIdentity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($otherIdentity, ['email' => 'other-owner@example.com']);
        $otherPrincipal = StrTestHelper::generateUuid();
        DB::table('account_principals')->insert(['id' => $otherPrincipal, 'identity_id' => (string) $otherIdentity, 'account_id' => $account]);
        $group = StrTestHelper::generateUuid();
        $role = StrTestHelper::generateUuid();
        DB::table('account_principal_groups')->insert(['id' => $group, 'account_id' => $account, 'name' => 'Renamed administrators', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('account_roles')->insert(['id' => $role, 'account_id' => null, 'name' => 'Owner']);
        DB::table('account_principal_group_role_attachments')->insert(['principal_group_id' => $group, 'role_id' => $role]);
        foreach ([$principal, $otherPrincipal] as $member) {
            DB::table('account_principal_group_memberships')->insert(['id' => StrTestHelper::generateUuid(), 'principal_id' => $member, 'principal_group_id' => $group]);
        }

        $this->assertReadEligibility($identityIdentifier, false);
        $this->expectException(IdentityWithdrawalNotAllowedException::class);
        $this->app()->make(WithdrawFromServiceInterface::class)->process(new WithdrawFromServiceInput($identityIdentifier), new WithdrawFromServiceOutput());
    }

    /** @return array<string, array{string, bool, bool}> */
    public static function allowedOwnerMemberships(): array
    {
        return [
            'individual system Owner' => ['individual', true, true],
            'corporate custom role named Owner' => ['corporation', false, true],
            'corporate system Owner belonging to another principal' => ['corporation', true, false],
        ];
    }

    #[DataProvider('allowedOwnerMemberships')]
    public function testOnlyTheSubjectsCorporateSystemOwnerMembershipBlocksWithdrawal(string $type, bool $isSystemRole, bool $isSubjectMember): void
    {
        $identityIdentifier = $this->createIdentityArchive();
        [$account, $principal] = $this->createMembership($identityIdentifier, $type);
        if (! $isSubjectMember) {
            $otherIdentity = new IdentityIdentifier(StrTestHelper::generateUuid());
            CreateIdentity::create($otherIdentity, ['email' => 'other@example.com']);
            $principal = StrTestHelper::generateUuid();
            DB::table('account_principals')->insert(['id' => $principal, 'identity_id' => (string) $otherIdentity, 'account_id' => $account]);
        }
        $group = StrTestHelper::generateUuid();
        $role = StrTestHelper::generateUuid();
        DB::table('account_principal_groups')->insert(['id' => $group, 'account_id' => $account, 'name' => 'Administrators', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('account_roles')->insert(['id' => $role, 'account_id' => $isSystemRole ? null : $account, 'name' => 'Owner']);
        DB::table('account_principal_group_role_attachments')->insert(['principal_group_id' => $group, 'role_id' => $role]);
        DB::table('account_principal_group_memberships')->insert(['id' => StrTestHelper::generateUuid(), 'principal_id' => $principal, 'principal_group_id' => $group]);

        $this->app()->make(WithdrawFromServiceInterface::class)->process(new WithdrawFromServiceInput($identityIdentifier), new WithdrawFromServiceOutput());

        $this->assertDatabaseHas('archived_principals', ['identity_id' => (string) $identityIdentifier, 'account_id' => $account]);
        $this->assertDatabaseMissing('account_principals', ['identity_id' => (string) $identityIdentifier]);
        if ($type === 'corporation') {
            $this->assertDatabaseHas('accounts', ['id' => $account]);
        } else {
            $this->assertDatabaseMissing('accounts', ['id' => $account]);
        }
    }

    /** @return array<string, array{string, bool}> */
    public static function matchingEmailMemberships(): array
    {
        return [
            'individual member' => ['individual', false],
            'individual Owner' => ['individual', true],
            'corporate member' => ['corporation', false],
            'corporate Owner' => ['corporation', true],
        ];
    }

    #[DataProvider('matchingEmailMemberships')]
    public function testMatchingAccountEmailOnlyBlocksCorporateMembershipsRegardlessOfRole(string $type, bool $isOwner): void
    {
        $identityIdentifier = $this->createIdentityArchive();
        [$otherAccount] = $this->createMembership($identityIdentifier, 'individual');
        [$account, $principal] = $this->createMembership($identityIdentifier, $type);
        DB::table('accounts')->where('id', $account)->update(['email' => 'TEST@EXAMPLE.COM']);
        if ($isOwner) {
            $group = StrTestHelper::generateUuid();
            $role = StrTestHelper::generateUuid();
            DB::table('account_principal_groups')->insert(['id' => $group, 'account_id' => $account, 'name' => 'Administrators', 'created_at' => now(), 'updated_at' => now()]);
            DB::table('account_roles')->insert(['id' => $role, 'account_id' => null, 'name' => 'Owner']);
            DB::table('account_principal_group_role_attachments')->insert(['principal_group_id' => $group, 'role_id' => $role]);
            DB::table('account_principal_group_memberships')->insert(['id' => StrTestHelper::generateUuid(), 'principal_id' => $principal, 'principal_group_id' => $group]);
        }
        $this->assertReadEligibility($identityIdentifier, $type === 'individual');
        if ($type === 'individual') {
            $this->app()->make(WithdrawFromServiceInterface::class)->process(new WithdrawFromServiceInput($identityIdentifier), new WithdrawFromServiceOutput());

            $this->assertDatabaseMissing('accounts', ['id' => $account]);
            $this->assertDatabaseMissing('accounts', ['id' => $otherAccount]);
            $this->assertDatabaseMissing('account_principals', ['id' => $principal]);
            $this->assertDatabaseHas('archived_accounts', ['account_id' => $account]);
            $this->assertDatabaseHas('archived_principals', ['principal_id' => $principal]);

            return;
        }

        try {
            $this->app()->make(WithdrawFromServiceInterface::class)->process(new WithdrawFromServiceInput($identityIdentifier), new WithdrawFromServiceOutput());
            $this->fail('Matching corporate account email must prevent withdrawal.');
        } catch (IdentityWithdrawalNotAllowedException) {
            $this->assertDatabaseHas('accounts', ['id' => $account]);
            $this->assertDatabaseHas('accounts', ['id' => $otherAccount]);
            $this->assertDatabaseHas('account_principals', ['id' => $principal]);
            $this->assertDatabaseCount('archived_accounts', 0);
            $this->assertDatabaseCount('archived_principals', 0);
        }
    }

    public function testNoMembershipIsIneligible(): void
    {
        $identityIdentifier = $this->createIdentityArchive();
        $this->assertReadEligibility($identityIdentifier, false);
    }

    private function assertReadEligibility(IdentityIdentifier $identityIdentifier, bool $expected): void
    {
        $accounts = DB::table('accounts')->count();
        $archives = DB::table('archived_accounts')->count();
        $action = new GetWithdrawalEligibilityAction(
            $this->app()->make(WithdrawalEligibilityServiceInterface::class),
            new ActorContext($identityIdentifier, Language::JAPANESE),
            new NullLogger(),
        );

        $response = $action();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['canWithdraw' => $expected], $response->getData(true));
        $this->assertSame($accounts, DB::table('accounts')->count());
        $this->assertSame($archives, DB::table('archived_accounts')->count());
    }

    private function createIdentityArchive(): IdentityIdentifier
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);
        DB::table('archived_identities')->insert(['id' => StrTestHelper::generateUuid(), 'identity_id' => (string) $identityIdentifier, 'language' => 'ja', 'identity_created_at' => now(), 'archived_at' => now()]);

        return $identityIdentifier;
    }

    /** @return array{string, string} */
    private function createMembership(IdentityIdentifier $identityIdentifier, ?string $type, string $category = 'general'): array
    {
        $account = StrTestHelper::generateUuid();
        $principal = StrTestHelper::generateUuid();
        CreateAccount::create($account, ['type' => $type, 'category' => $category, 'name' => 'Private name', 'status' => $type === null ? 'pending' : 'active']);
        DB::table('account_principals')->insert(['id' => $principal, 'identity_id' => (string) $identityIdentifier, 'account_id' => $account]);

        return [$account, $principal];
    }
}
