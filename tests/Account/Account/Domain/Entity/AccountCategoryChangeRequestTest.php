<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\Entity\AccountCategoryChangeRequest;
use Source\Account\Account\Domain\Exception\InvalidAccountCategoryChangeRequestApprovalException;
use Source\Account\Account\Domain\Exception\InvalidAccountCategoryChangeRequestRejectionException;
use Source\Account\Account\Domain\Exception\InvalidAccountCategoryChangeRequestWithdrawalException;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestStatus;
use Source\Account\Account\Domain\ValueObject\RejectionReason;
use Source\Account\Account\Domain\ValueObject\RejectionReasonCode;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\CreateAccountCategoryChangeRequestAccount;
use Tests\Helper\StrTestHelper;

class AccountCategoryChangeRequestTest extends TestCase
{
    public function testCanCreatePendingRequest(): void
    {
        $requestedAt = new DateTimeImmutable('2026-08-11 00:00:00');
        $request = new AccountCategoryChangeRequest(
            new AccountCategoryChangeRequestIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            AccountCategory::GENERAL,
            AccountCategory::AGENCY,
            AccountCategoryChangeRequestStatus::PENDING,
            $requestedAt,
            null,
            null,
            null,
            CreateAccountCategoryChangeRequestAccount::create(),
        );

        $this->assertSame(AccountCategory::GENERAL, $request->currentAccountCategory());
        $this->assertSame(AccountCategory::AGENCY, $request->requestedAccountCategory());
        $this->assertSame(AccountCategoryChangeRequestStatus::PENDING, $request->status());
        $this->assertSame($requestedAt, $request->requestedAt());
    }

    public function testApproveFromPending(): void
    {
        $request = $this->createRequest(AccountCategoryChangeRequestStatus::PENDING);
        $reviewer = new AccountIdentifier(StrTestHelper::generateUuid());

        $request->approve($reviewer);

        $this->assertSame(AccountCategoryChangeRequestStatus::APPROVED, $request->status());
        $this->assertSame((string) $reviewer, (string) $request->reviewedBy());
        $this->assertNotNull($request->reviewedAt());
        $this->assertNull($request->rejectionReason());
    }

    public function testCannotApproveFromApproved(): void
    {
        $request = $this->createRequest(AccountCategoryChangeRequestStatus::APPROVED);

        $this->expectException(InvalidAccountCategoryChangeRequestApprovalException::class);

        $request->approve(new AccountIdentifier(StrTestHelper::generateUuid()));
    }

    public function testRejectFromPending(): void
    {
        $request = $this->createRequest(AccountCategoryChangeRequestStatus::PENDING);
        $reviewer = new AccountIdentifier(StrTestHelper::generateUuid());
        $reason = new RejectionReason(RejectionReasonCode::OTHER, 'missing information');

        $request->reject($reviewer, $reason);

        $this->assertSame(AccountCategoryChangeRequestStatus::REJECTED, $request->status());
        $this->assertSame((string) $reviewer, (string) $request->reviewedBy());
        $this->assertNotNull($request->reviewedAt());
        $this->assertSame($reason, $request->rejectionReason());
    }

    public function testCannotRejectFromApproved(): void
    {
        $request = $this->createRequest(AccountCategoryChangeRequestStatus::APPROVED);

        $this->expectException(InvalidAccountCategoryChangeRequestRejectionException::class);

        $request->reject(new AccountIdentifier(StrTestHelper::generateUuid()), new RejectionReason(RejectionReasonCode::OTHER, 'missing information'));
    }

    public function testCannotRejectFromRejected(): void
    {
        $request = $this->createRequest(AccountCategoryChangeRequestStatus::REJECTED);

        $this->expectException(InvalidAccountCategoryChangeRequestRejectionException::class);

        $request->reject(new AccountIdentifier(StrTestHelper::generateUuid()), new RejectionReason(RejectionReasonCode::OTHER, 'missing information'));
    }

    public function testWithdrawPreservesCategoriesAndRequestTimeWithoutReview(): void
    {
        $request = $this->createRequest(AccountCategoryChangeRequestStatus::PENDING);
        $requestedAt = $request->requestedAt();
        $request->withdraw();
        $this->assertSame(AccountCategoryChangeRequestStatus::WITHDRAWN, $request->status());
        $this->assertSame(AccountCategory::GENERAL, $request->currentAccountCategory());
        $this->assertSame(AccountCategory::AGENCY, $request->requestedAccountCategory());
        $this->assertSame($requestedAt, $request->requestedAt());
        $this->assertNull($request->reviewedBy());
        $this->assertNull($request->reviewedAt());
        $this->assertNull($request->rejectionReason());
    }

    public function testAllTerminalStatesRejectAllOperationsWithoutChangingHistory(): void
    {
        foreach ([AccountCategoryChangeRequestStatus::APPROVED, AccountCategoryChangeRequestStatus::REJECTED, AccountCategoryChangeRequestStatus::WITHDRAWN] as $status) {
            foreach (['approve', 'reject', 'withdraw'] as $operation) {
                $reviewer = new AccountIdentifier(StrTestHelper::generateUuid());
                $reason = new RejectionReason(RejectionReasonCode::OTHER, 'original reason');
                $reviewedAt = new DateTimeImmutable('2026-08-12');
                $request = new AccountCategoryChangeRequest(new AccountCategoryChangeRequestIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()), AccountCategory::GENERAL, AccountCategory::AGENCY, $status, new DateTimeImmutable('2026-08-11'), $reviewer, $reviewedAt, $reason, CreateAccountCategoryChangeRequestAccount::create());

                try {
                    match ($operation) {
                        'approve' => $request->approve($reviewer),
                        'reject' => $request->reject($reviewer, $reason),
                        'withdraw' => $request->withdraw(),
                    };
                    $this->fail('Terminal state must reject operation');
                } catch (InvalidAccountCategoryChangeRequestApprovalException|InvalidAccountCategoryChangeRequestRejectionException|InvalidAccountCategoryChangeRequestWithdrawalException) {
                    $this->assertSame($status, $request->status());
                    $this->assertSame($reviewer, $request->reviewedBy());
                    $this->assertSame($reviewedAt, $request->reviewedAt());
                    $this->assertSame($reason, $request->rejectionReason());
                }
            }
        }
    }

    private function createRequest(AccountCategoryChangeRequestStatus $status): AccountCategoryChangeRequest
    {
        return new AccountCategoryChangeRequest(
            new AccountCategoryChangeRequestIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            AccountCategory::GENERAL,
            AccountCategory::AGENCY,
            $status,
            new DateTimeImmutable(),
            null,
            null,
            null,
            CreateAccountCategoryChangeRequestAccount::create(),
        );
    }
}
