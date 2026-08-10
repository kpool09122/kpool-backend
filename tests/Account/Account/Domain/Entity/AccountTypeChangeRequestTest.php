<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\Entity\AccountTypeChangeRequest;
use Source\Account\Account\Domain\Exception\InvalidAccountTypeChangeRequestApprovalException;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Account\Domain\ValueObject\AccountTypeChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountTypeChangeRequestStatus;
use Source\Account\Account\Domain\ValueObject\RejectionReason;
use Source\Account\Account\Domain\ValueObject\RejectionReasonCode;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;

class AccountTypeChangeRequestTest extends TestCase
{
    public function testCanCreatePendingRequest(): void
    {
        $requestedAt = new DateTimeImmutable('2026-08-11 00:00:00');
        $request = new AccountTypeChangeRequest(
            new AccountTypeChangeRequestIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            AccountType::INDIVIDUAL,
            AccountType::CORPORATION,
            AccountTypeChangeRequestStatus::PENDING,
            $requestedAt,
            null,
            null,
            null,
        );

        $this->assertSame(AccountType::INDIVIDUAL, $request->currentAccountType());
        $this->assertSame(AccountType::CORPORATION, $request->requestedAccountType());
        $this->assertSame(AccountTypeChangeRequestStatus::PENDING, $request->status());
        $this->assertSame($requestedAt, $request->requestedAt());
    }

    public function testApproveFromPending(): void
    {
        $request = $this->createRequest(AccountTypeChangeRequestStatus::PENDING);
        $reviewer = new AccountIdentifier(StrTestHelper::generateUuid());

        $request->approve($reviewer);

        $this->assertSame(AccountTypeChangeRequestStatus::APPROVED, $request->status());
        $this->assertSame((string) $reviewer, (string) $request->reviewedBy());
        $this->assertNotNull($request->reviewedAt());
        $this->assertNull($request->rejectionReason());
    }

    public function testCannotApproveFromApproved(): void
    {
        $request = $this->createRequest(AccountTypeChangeRequestStatus::APPROVED);

        $this->expectException(InvalidAccountTypeChangeRequestApprovalException::class);

        $request->approve(new AccountIdentifier(StrTestHelper::generateUuid()));
    }

    public function testRejectFromPending(): void
    {
        $request = $this->createRequest(AccountTypeChangeRequestStatus::PENDING);
        $reviewer = new AccountIdentifier(StrTestHelper::generateUuid());
        $reason = new RejectionReason(RejectionReasonCode::OTHER, 'missing information');

        $request->reject($reviewer, $reason);

        $this->assertSame(AccountTypeChangeRequestStatus::REJECTED, $request->status());
        $this->assertSame((string) $reviewer, (string) $request->reviewedBy());
        $this->assertSame($reason, $request->rejectionReason());
    }

    private function createRequest(AccountTypeChangeRequestStatus $status): AccountTypeChangeRequest
    {
        return new AccountTypeChangeRequest(
            new AccountTypeChangeRequestIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            AccountType::INDIVIDUAL,
            AccountType::CORPORATION,
            $status,
            new DateTimeImmutable(),
            null,
            null,
            null,
        );
    }
}
