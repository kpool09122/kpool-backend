<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
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
    public function testLatestStateAndNeedForApprovalComeFromSeparateHistories(): void
    {
        $id = CreateAccountSnapshotChangeRequest::prepare();
        $query = $this->app()->make(GetLatestAccountSnapshotChangeRequestInterface::class);
        $input = new GetLatestAccountSnapshotChangeRequestInput($id, CreateAccountSnapshot::principal($id));
        $output = new GetLatestAccountSnapshotChangeRequestOutput();
        $query->process($input, $output);
        self::assertSame(['request' => null, 'changeRequiresApproval' => false], $output->toArray());
        $requestId = CreateAccountSnapshotChangeRequest::create($id);
        DB::table('account_category_change_requests')->insert(['id' => StrTestHelper::generateUuid(), 'account_id' => (string) $id, 'current_account_category' => 'general', 'requested_account_category' => 'talent', 'status' => 'approved', 'requested_at' => now(), 'snapshot_id' => DB::table('account_snapshots')->where('account_id', (string) $id)->value('id')]);
        $query->process($input, $output);
        self::assertTrue($output->toArray()['changeRequiresApproval']);
        self::assertSame((string) $requestId, $output->toArray()['request']['requestIdentifier'] ?? null);
        CreateAccountSnapshotChangeRequest::reject($requestId, $id);
        $nextId = CreateAccountSnapshotChangeRequest::create($id);
        DB::table('account_snapshot_change_requests')->where('account_id', (string) $id)->update(['requested_at' => '2026-11-25']);
        $query->process($input, $output);
        self::assertSame((string) $nextId, $output->toArray()['request']['requestIdentifier'] ?? null);
    }
}
