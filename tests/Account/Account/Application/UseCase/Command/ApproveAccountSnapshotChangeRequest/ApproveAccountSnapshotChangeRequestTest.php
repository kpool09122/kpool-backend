<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\ApproveAccountSnapshotChangeRequest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequiresApprovalException;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountSnapshotChangeRequest\ApproveAccountSnapshotChangeRequestInput;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountSnapshotChangeRequest\ApproveAccountSnapshotChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountSnapshotChangeRequest\ApproveAccountSnapshotChangeRequestOutput;
use Source\Account\Account\Application\UseCase\Command\UpdateAccount\UpdateAccountInput;
use Source\Account\Account\Application\UseCase\Command\UpdateAccount\UpdateAccountInterface;
use Source\Account\Account\Application\UseCase\Command\UpdateAccount\UpdateAccountOutput;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshot\GetAccountSnapshotInput;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshot\GetAccountSnapshotInterface;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest\GetAccountSnapshotChangeRequestInput;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest\GetAccountSnapshotChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest\GetAccountSnapshotChangeRequestOutput;
use Source\Account\Account\Domain\Entity\AccountSnapshotChangeRequest;
use Source\Account\Account\Domain\Repository\AccountSnapshotChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountSnapshotRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotStatus;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Tests\Helper\CreateAccountSnapshot;
use Tests\Helper\CreateAccountSnapshotChangeRequest;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class ApproveAccountSnapshotChangeRequestTest extends TestCase
{
    public function testApprovalSavesApprovedSnapshotAndPreservesHistoricReferences(): void
    {
        Storage::fake('verification-documents');
        $policy = $this->createStub(PolicyEvaluatorInterface::class);
        $policy->method('evaluate')->willReturn(true);
        $this->app()->instance(PolicyEvaluatorInterface::class, $policy);
        $id = CreateAccountSnapshot::account();
        $first = CreateAccountSnapshot::save($id);
        $categoryRequestId = StrTestHelper::generateUuid();
        DB::table('account_category_change_requests')->insert(['id' => $categoryRequestId, 'account_id' => (string) $id, 'current_account_category' => 'general', 'requested_account_category' => 'talent', 'status' => 'approved', 'requested_at' => now(), 'snapshot_id' => (string) $first->snapshotIdentifier(), 'approved_snapshot_id' => (string) $first->snapshotIdentifier()]);
        $requestId = CreateAccountSnapshotChangeRequest::create($id);
        $repository = $this->app()->make(AccountSnapshotRepositoryInterface::class);
        $output = new ApproveAccountSnapshotChangeRequestOutput();
        $this->app()->make(ApproveAccountSnapshotChangeRequestInterface::class)->process(new ApproveAccountSnapshotChangeRequestInput($requestId, CreateAccountSnapshot::principal($id)), $output);
        $current = $repository->findCurrent($id);
        self::assertNotNull($current);
        $data = $output->toArray();
        if ($data === []) {
            self::fail('Expected populated output.');
        }
        self::assertSame(AccountSnapshotStatus::APPROVED, $current->status());
        self::assertSame('Proposed name', $current->account()->name());
        self::assertSame('approved', $data['status']);
        self::assertSame((string) $current->snapshotIdentifier(), $data['reviewedSnapshotIdentifier']);
        self::assertSame((string) $first->snapshotIdentifier(), $data['snapshotIdentifier']);
        self::assertSame('Test Account', $repository->findById($first->snapshotIdentifier())?->account()->name());
        self::assertSame((string) $first->snapshotIdentifier(), DB::table('account_category_change_requests')->where('id', $categoryRequestId)->value('approved_snapshot_id'));
        self::assertDatabaseCount('account_snapshots', 2);
        self::assertSame('passport bytes', Storage::disk('verification-documents')->get((string) $current->documents()[0]->documentPath()));
        $request = $this->app()->make(AccountSnapshotChangeRequestRepositoryInterface::class)->findById($requestId);
        self::assertNotNull($request);
        self::assertNotNull($request->reviewedAt());
        self::assertNotNull($request->reviewedBy());
        DB::table('accounts')->where('id', (string) $id)->update(['name' => 'Proposed name']);
        Storage::disk('verification-documents')->put($id . '/passport.pdf', 'passport bytes');
        self::assertSame((string) $current->snapshotIdentifier(), (string) CreateAccountSnapshot::save($id)->snapshotIdentifier());
        DB::table('accounts')->where('id', (string) $id)->update(['name' => 'Another change']);
        $this->expectException(AccountSnapshotChangeRequiresApprovalException::class);
        CreateAccountSnapshot::save($id);
    }

    public function testApprovalIsDeniedWithoutManagementGrant(): void
    {
        $id = CreateAccountSnapshotChangeRequest::prepare();
        $requestId = CreateAccountSnapshotChangeRequest::create($id);
        $policy = $this->createStub(PolicyEvaluatorInterface::class);
        $policy->method('evaluate')->willReturn(false);
        $this->app()->instance(PolicyEvaluatorInterface::class, $policy);
        $this->expectException(AccountSnapshotChangeRequestForbiddenException::class);
        $this->app()->make(ApproveAccountSnapshotChangeRequestInterface::class)->process(new ApproveAccountSnapshotChangeRequestInput($requestId, CreateAccountSnapshot::principal($id)), new ApproveAccountSnapshotChangeRequestOutput());
    }

    public function testCallerTransactionRollsBackRealSnapshotAndApprovalWritesOnLateFailure(): void
    {
        $id = CreateAccountSnapshotChangeRequest::prepare();
        $requestId = CreateAccountSnapshotChangeRequest::create($id);
        $real = $this->app()->make(AccountSnapshotChangeRequestRepositoryInterface::class);
        $repository = $this->createMock(AccountSnapshotChangeRequestRepositoryInterface::class);
        $repository->method('findById')->willReturnCallback($real->findById(...));
        $repository->expects(self::once())->method('save')->willReturnCallback(function (AccountSnapshotChangeRequest $request) use ($real): void {
            $real->save($request);
            self::assertDatabaseCount('account_snapshots', 2);
            self::assertDatabaseHas('account_snapshot_change_requests', ['id' => (string) $request->requestIdentifier(), 'status' => 'approved', 'reviewed_snapshot_id' => (string) $request->reviewedSnapshotIdentifier()]);

            throw new RuntimeException('failure after approval write');
        });
        $this->app()->instance(AccountSnapshotChangeRequestRepositoryInterface::class, $repository);
        $files = Storage::disk('verification-documents')->allFiles();
        DB::beginTransaction();

        try {
            $this->app()->make(ApproveAccountSnapshotChangeRequestInterface::class)->process(new ApproveAccountSnapshotChangeRequestInput($requestId, CreateAccountSnapshot::principal($id)), new ApproveAccountSnapshotChangeRequestOutput());
            self::fail('Expected persistence failure.');
        } catch (RuntimeException $exception) {
            self::assertSame('failure after approval write', $exception->getMessage());
        } finally {
            DB::rollBack();
        }
        self::assertDatabaseCount('account_snapshots', 1);
        $pending = $real->findById($requestId);
        self::assertNotNull($pending);
        self::assertSame('pending', $pending->status()->value);
        self::assertNull($pending->reviewedSnapshotIdentifier());
        self::assertSame($files, Storage::disk('verification-documents')->allFiles());
    }

    public function testReviewPreservesHistoryAndUnlocksEdits(): void
    {
        $id = CreateAccountSnapshotChangeRequest::prepare();
        $requestId = CreateAccountSnapshotChangeRequest::create($id);
        $accountSnapshotChangeRequestRepository = $this->app()->make(AccountSnapshotChangeRequestRepositoryInterface::class);
        $accountSnapshotRepository = $this->app()->make(AccountSnapshotRepositoryInterface::class);
        $original = $accountSnapshotRepository->findCurrent($id);
        self::assertNotNull($original);
        $request = $accountSnapshotChangeRequestRepository->findById($requestId);
        self::assertNotNull($request);
        $principal = CreateAccountSnapshot::principal($id);

        $this->app()->make(ApproveAccountSnapshotChangeRequestInterface::class)->process(new ApproveAccountSnapshotChangeRequestInput($requestId, $principal), new ApproveAccountSnapshotChangeRequestOutput());
        $request = $accountSnapshotChangeRequestRepository->findById($requestId);
        self::assertNotNull($request);

        $reviewedId = $request->reviewedSnapshotIdentifier();
        self::assertNotNull($reviewedId);
        $reviewed = $accountSnapshotRepository->findById($reviewedId);
        self::assertNotNull($reviewed);
        self::assertSame(AccountSnapshotStatus::APPROVED, $reviewed->status());
        self::assertEquals($reviewedId, $accountSnapshotRepository->findCurrent($id)?->snapshotIdentifier());
        self::assertEquals($reviewedId, $accountSnapshotChangeRequestRepository->findById($requestId)?->reviewedSnapshotIdentifier());

        $current = $this->app()->make(GetAccountSnapshotInterface::class)->process(new GetAccountSnapshotInput($id, CreateAccountSnapshot::principal($id)));
        self::assertSame('approved', $current->toArray()['status']);
        self::assertSame((string) ($reviewedId), $current->snapshotIdentifier());

        $this->app()->make(UpdateAccountInterface::class)->process(
            new UpdateAccountInput($id, CreateAccountSnapshot::principal($id), new AccountName('Next application')),
            new UpdateAccountOutput(),
        );
        Storage::disk('verification-documents')->put($id . '/passport.pdf', 'replacement');
        $output = new GetAccountSnapshotChangeRequestOutput();
        $this->app()->make(GetAccountSnapshotChangeRequestInterface::class)->process(new GetAccountSnapshotChangeRequestInput($requestId, CreateAccountSnapshot::principal($id)), $output);
        $data = $output->toArray();
        if ($data === []) {
            self::fail('Expected reviewed request.');
        }
        self::assertSame('Proposed name', $data['proposal']['account']['name']);
        self::assertSame('passport bytes', Storage::disk('verification-documents')->get((string) $reviewed->documents()[0]->documentPath()));
    }
}
