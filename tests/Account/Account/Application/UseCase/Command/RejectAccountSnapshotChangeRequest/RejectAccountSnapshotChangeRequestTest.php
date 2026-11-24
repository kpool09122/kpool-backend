<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\RejectAccountSnapshotChangeRequest;

use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Application\Exception\AccountSnapshotChangeRequestForbiddenException;
use Source\Account\Account\Application\UseCase\Command\RejectAccountSnapshotChangeRequest\RejectAccountSnapshotChangeRequestInput;
use Source\Account\Account\Application\UseCase\Command\RejectAccountSnapshotChangeRequest\RejectAccountSnapshotChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Command\RejectAccountSnapshotChangeRequest\RejectAccountSnapshotChangeRequestOutput;
use Source\Account\Account\Application\UseCase\Command\UpdateAccount\UpdateAccountInput;
use Source\Account\Account\Application\UseCase\Command\UpdateAccount\UpdateAccountInterface;
use Source\Account\Account\Application\UseCase\Command\UpdateAccount\UpdateAccountOutput;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshot\GetAccountSnapshotInput;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshot\GetAccountSnapshotInterface;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest\GetAccountSnapshotChangeRequestInput;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest\GetAccountSnapshotChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest\GetAccountSnapshotChangeRequestOutput;
use Source\Account\Account\Domain\Repository\AccountSnapshotChangeRequestRepositoryInterface;
use Source\Account\Account\Domain\Repository\AccountSnapshotRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotStatus;
use Source\Account\Account\Domain\ValueObject\RejectionReason;
use Source\Account\Account\Domain\ValueObject\RejectionReasonCode;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Tests\Helper\CreateAccountSnapshot;
use Tests\Helper\CreateAccountSnapshotChangeRequest;
use Tests\TestCase;

#[Group('useDb')]
class RejectAccountSnapshotChangeRequestTest extends TestCase
{
    public function testRejectionPreservesCurrentAndAllowsResubmission(): void
    {
        $id = CreateAccountSnapshotChangeRequest::prepare();
        $requestId = CreateAccountSnapshotChangeRequest::create($id);
        $current = $this->app()->make(AccountSnapshotRepositoryInterface::class)->findCurrent($id);
        $output = new RejectAccountSnapshotChangeRequestOutput();
        $this->app()->make(RejectAccountSnapshotChangeRequestInterface::class)->process(new RejectAccountSnapshotChangeRequestInput($requestId, CreateAccountSnapshot::principal($id), new RejectionReason(RejectionReasonCode::OTHER, '再提出してください')), $output);
        $data = $output->toArray();
        if ($data === []) {
            self::fail('Expected populated output.');
        }
        self::assertSame('rejected', $data['status']);
        self::assertSame(['code' => 'other', 'detail' => '再提出してください'], $data['rejectionReason']);
        self::assertNotNull($data['reviewedAt']);
        self::assertNotNull($data['reviewedSnapshotIdentifier']);
        self::assertDatabaseHas('account_snapshots', ['id' => $data['reviewedSnapshotIdentifier'], 'status' => 'rejected', 'account_name' => 'Proposed name']);
        self::assertEquals($current, $this->app()->make(AccountSnapshotRepositoryInterface::class)->findCurrent($id));
        self::assertDatabaseCount('account_snapshots', 2);
        self::assertNotEquals($requestId, CreateAccountSnapshotChangeRequest::create($id));
    }

    public function testCannotRejectWithoutManagementPermission(): void
    {
        $id = CreateAccountSnapshotChangeRequest::prepare();
        $requestId = CreateAccountSnapshotChangeRequest::create($id);
        $policy = $this->createStub(PolicyEvaluatorInterface::class);
        $policy->method('evaluate')->willReturn(false);
        $this->app()->instance(PolicyEvaluatorInterface::class, $policy);
        $this->expectException(AccountSnapshotChangeRequestForbiddenException::class);
        $this->app()->make(RejectAccountSnapshotChangeRequestInterface::class)->process(new RejectAccountSnapshotChangeRequestInput($requestId, CreateAccountSnapshot::principal($id), new RejectionReason(RejectionReasonCode::OTHER, '再提出')), new RejectAccountSnapshotChangeRequestOutput());
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

        $this->app()->make(RejectAccountSnapshotChangeRequestInterface::class)->process(new RejectAccountSnapshotChangeRequestInput($requestId, $principal, new RejectionReason(RejectionReasonCode::OTHER, '再提出')), new RejectAccountSnapshotChangeRequestOutput());
        $request = $accountSnapshotChangeRequestRepository->findById($requestId);
        self::assertNotNull($request);

        $reviewedId = $request->reviewedSnapshotIdentifier();
        self::assertNotNull($reviewedId);
        $reviewed = $accountSnapshotRepository->findById($reviewedId);
        self::assertNotNull($reviewed);
        self::assertSame(AccountSnapshotStatus::REJECTED, $reviewed->status());
        self::assertEquals($original->snapshotIdentifier(), $accountSnapshotRepository->findCurrent($id)?->snapshotIdentifier());
        self::assertEquals($reviewedId, $accountSnapshotChangeRequestRepository->findById($requestId)?->reviewedSnapshotIdentifier());

        $current = $this->app()->make(GetAccountSnapshotInterface::class)->process(new GetAccountSnapshotInput($id, CreateAccountSnapshot::principal($id)));
        self::assertSame('approved', $current->toArray()['status']);
        self::assertSame((string) ($original->snapshotIdentifier()), $current->snapshotIdentifier());

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
