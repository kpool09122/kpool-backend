<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\UseCase\Query\GetLatestAccountSnapshotChangeRequest\GetLatestAccountSnapshotChangeRequestInput;
use Source\Account\Account\Application\UseCase\Query\GetLatestAccountSnapshotChangeRequest\GetLatestAccountSnapshotChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Query\GetLatestAccountSnapshotChangeRequest\GetLatestAccountSnapshotChangeRequestOutput;
use Tests\Helper\CreateAccountSnapshot;
use Tests\Helper\CreateAccountSnapshotChangeRequest;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class GetLatestAccountSnapshotChangeRequestTest extends TestCase
{
    public function testHasChangesTracksProfileAndDocumentContents(): void
    {
        $id = CreateAccountSnapshotChangeRequest::prepare();
        $query = $this->app()->make(GetLatestAccountSnapshotChangeRequestInterface::class);
        $input = new GetLatestAccountSnapshotChangeRequestInput($id, CreateAccountSnapshot::principal($id));
        $output = new GetLatestAccountSnapshotChangeRequestOutput();
        $query->process($input, $output);
        self::assertFalse($output->toArray()['hasChanges']);
        DB::table('accounts')->where('id', (string) $id)->update(['phone' => null]);
        $query->process($input, $output);
        self::assertTrue($output->toArray()['hasChanges']);
        DB::table('accounts')->where('id', (string) $id)->update(['phone' => '+33123456789']);
        $query->process($input, $output);
        self::assertFalse($output->toArray()['hasChanges']);
        Storage::disk('verification-documents')->put((string) $id.'/replacement.pdf', 'passport bytes');
        DB::table('account_documents')->where('account_id', (string) $id)->where('document_type', 'passport')->update([
            'document_path' => (string) $id.'/replacement.pdf',
            'uploaded_at' => now(),
        ]);
        $query->process($input, $output);
        self::assertFalse($output->toArray()['hasChanges']);
        Storage::disk('verification-documents')->put((string) $id.'/replacement.pdf', 'changed bytes');
        $query->process($input, $output);
        self::assertTrue($output->toArray()['hasChanges']);
        CreateAccountSnapshot::save($id);
        $query->process($input, $output);
        self::assertFalse($output->toArray()['hasChanges']);
    }

    public function testCategoryPendingLockEndsAfterReviewOrWithdrawal(): void
    {
        $id = CreateAccountSnapshotChangeRequest::prepare();
        $requestId = StrTestHelper::generateUuid();
        DB::table('account_category_change_requests')->insert(['id' => $requestId, 'account_id' => (string) $id, 'current_account_category' => 'general', 'requested_account_category' => 'talent', 'status' => 'pending', 'requested_at' => now(), 'snapshot_id' => DB::table('account_snapshots')->where('account_id', (string) $id)->value('id')]);
        $query = $this->app()->make(GetLatestAccountSnapshotChangeRequestInterface::class);
        $input = new GetLatestAccountSnapshotChangeRequestInput($id, CreateAccountSnapshot::principal($id));
        $output = new GetLatestAccountSnapshotChangeRequestOutput();
        $query->process($input, $output);
        self::assertTrue($output->toArray()['categoryChangePending']);
        self::assertFalse($output->toArray()['changeRequiresApproval']);
        foreach (['withdrawn', 'rejected', 'approved'] as $status) {
            DB::table('account_category_change_requests')->where('id', $requestId)->update(['status' => $status]);
            $query->process($input, $output);
            self::assertFalse($output->toArray()['categoryChangePending']);
            self::assertSame($status === 'approved', $output->toArray()['changeRequiresApproval']);
        }
    }

    public function testLatestStateAndNeedForApprovalComeFromSeparateHistories(): void
    {
        $id = CreateAccountSnapshotChangeRequest::prepare();
        $query = $this->app()->make(GetLatestAccountSnapshotChangeRequestInterface::class);
        $input = new GetLatestAccountSnapshotChangeRequestInput($id, CreateAccountSnapshot::principal($id));
        $output = new GetLatestAccountSnapshotChangeRequestOutput();
        $query->process($input, $output);
        self::assertSame(['request' => null, 'changeRequiresApproval' => false, 'hasChanges' => false, 'categoryChangePending' => false], $output->toArray());
        $requestId = CreateAccountSnapshotChangeRequest::create($id);
        DB::table('account_category_change_requests')->insert(['id' => StrTestHelper::generateUuid(), 'account_id' => (string) $id, 'current_account_category' => 'general', 'requested_account_category' => 'talent', 'status' => 'approved', 'requested_at' => now(), 'snapshot_id' => DB::table('account_snapshots')->where('account_id', (string) $id)->value('id')]);
        $query->process($input, $output);
        self::assertTrue($output->toArray()['changeRequiresApproval']);
        self::assertTrue($output->toArray()['hasChanges']);
        self::assertSame((string) $requestId, $output->toArray()['request']['requestIdentifier'] ?? null);
        CreateAccountSnapshotChangeRequest::reject($requestId, $id);
        $nextId = CreateAccountSnapshotChangeRequest::create($id);
        DB::table('account_snapshot_change_requests')->where('account_id', (string) $id)->update(['requested_at' => '2026-11-25']);
        $query->process($input, $output);
        self::assertSame((string) $nextId, $output->toArray()['request']['requestIdentifier'] ?? null);
    }
}
