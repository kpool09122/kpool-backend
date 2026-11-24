<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\Exception\InvalidAccountSnapshotChangeRequestApprovalException;
use Source\Account\Account\Domain\Exception\InvalidAccountSnapshotChangeRequestRejectionException;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestStatus;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotIdentifier;
use Source\Account\Account\Domain\ValueObject\RejectionReason;
use Source\Account\Account\Domain\ValueObject\RejectionReasonCode;
use Tests\Helper\CreateAccountSnapshotChangeRequest;
use Tests\Helper\StrTestHelper;

class AccountSnapshotChangeRequestTest extends TestCase
{
    public function testApprovalLinksNewVersionWithoutChangingProposalOrSource(): void
    {
        $request = CreateAccountSnapshotChangeRequest::entity();
        $source = $request->snapshotIdentifier();
        $approved = new AccountSnapshotIdentifier(StrTestHelper::generateUuid());
        $request->approve($request->accountIdentifier(), $approved);
        self::assertSame(AccountSnapshotChangeRequestStatus::APPROVED, $request->status());
        self::assertSame($source, $request->snapshotIdentifier());
        self::assertSame($approved, $request->reviewedSnapshotIdentifier());
        self::assertNotNull($request->reviewedAt());
        self::assertSame($request->accountIdentifier(), $request->reviewedBy());
    }

    public function testTerminalStatesRejectBothReviewOperationsAndPreserveHistory(): void
    {
        foreach (['approved', 'rejected'] as $status) {
            foreach (['approve', 'reject'] as $operation) {
                $request = CreateAccountSnapshotChangeRequest::entity();
                $approved = new AccountSnapshotIdentifier(StrTestHelper::generateUuid());
                $reason = new RejectionReason(RejectionReasonCode::OTHER, '確認できません');
                if ($status === 'approved') {
                    $request->approve($request->accountIdentifier(), $approved);
                } else {
                    $request->reject($request->accountIdentifier(), $reason, $approved);
                }
                $reviewedAt = $request->reviewedAt();

                try {
                    if ($operation === 'approve') {
                        $request->approve($request->accountIdentifier(), $approved);
                    } else {
                        $request->reject($request->accountIdentifier(), $reason, $approved);
                    }
                    self::fail('Terminal request cannot be reviewed again.');
                } catch (InvalidAccountSnapshotChangeRequestApprovalException|InvalidAccountSnapshotChangeRequestRejectionException) {
                    self::assertSame($status, $request->status()->value);
                    self::assertSame($reviewedAt, $request->reviewedAt());
                    self::assertSame($approved, $request->reviewedSnapshotIdentifier());
                    self::assertSame($status === 'rejected' ? $reason : null, $request->rejectionReason());
                }
            }
        }
    }
}
