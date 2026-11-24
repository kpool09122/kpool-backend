<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestForbiddenException;
use Source\Account\Account\Application\UseCase\Query\ListAccountSnapshotChangeRequests\ListAccountSnapshotChangeRequestsInput;
use Source\Account\Account\Application\UseCase\Query\ListAccountSnapshotChangeRequests\ListAccountSnapshotChangeRequestsInterface;
use Source\Account\Account\Application\UseCase\Query\ListAccountSnapshotChangeRequests\ListAccountSnapshotChangeRequestsOutput;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Tests\Helper\CreateAccountSnapshot;
use Tests\Helper\CreateAccountSnapshotChangeRequest;
use Tests\TestCase;

#[Group('useDb')]
class ListAccountSnapshotChangeRequestsTest extends TestCase
{
    public function testFiltersPaginatesAndOrdersByTimeThenIdentifier(): void
    {
        $id = CreateAccountSnapshotChangeRequest::prepare();
        $first = CreateAccountSnapshotChangeRequest::create($id);
        CreateAccountSnapshotChangeRequest::reject($first, $id);
        $second = CreateAccountSnapshotChangeRequest::create($id);
        DB::table('account_snapshot_change_requests')->where('account_id', (string) $id)->update(['requested_at' => '2026-11-25']);
        $output = new ListAccountSnapshotChangeRequestsOutput();
        $query = $this->app()->make(ListAccountSnapshotChangeRequestsInterface::class);
        $query->process(new ListAccountSnapshotChangeRequestsInput(CreateAccountSnapshot::principal($id), perPage: 1, page: 1), $output);
        self::assertSame((string) $second, $output->toArray()['requests'][0]['requestIdentifier']);
        self::assertSame(2, $output->toArray()['pagination']['total']);
        $query->process(new ListAccountSnapshotChangeRequestsInput(CreateAccountSnapshot::principal($id), status: 'rejected'), $output);
        self::assertSame((string) $first, $output->toArray()['requests'][0]['requestIdentifier']);
        self::assertSame(1, $output->toArray()['pagination']['total']);
    }

    public function testOwnerWithoutManagementGrantCannotList(): void
    {
        $id = CreateAccountSnapshotChangeRequest::prepare();
        $policy = $this->createStub(PolicyEvaluatorInterface::class);
        $policy->method('evaluate')->willReturn(false);
        $this->app()->instance(PolicyEvaluatorInterface::class, $policy);
        $this->expectException(AccountSnapshotChangeRequestForbiddenException::class);
        $this->app()->make(ListAccountSnapshotChangeRequestsInterface::class)->process(new ListAccountSnapshotChangeRequestsInput(CreateAccountSnapshot::principal($id)), new ListAccountSnapshotChangeRequestsOutput());
    }
}
