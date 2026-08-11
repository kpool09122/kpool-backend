<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\ApproveAccountCategoryChangeRequest;

use DateTimeImmutable;
use Source\Account\Account\Application\UseCase\Command\ApproveAccountCategoryChangeRequest\ApproveAccountCategoryChangeRequestOutput;
use Source\Account\Account\Domain\Entity\AccountCategoryChangeRequest;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestStatus;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ApproveAccountCategoryChangeRequestOutputTest extends TestCase
{
    public function testToArrayWithRequest(): void
    {
        $requestIdentifier = new AccountCategoryChangeRequestIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $reviewedBy = new AccountIdentifier(StrTestHelper::generateUuid());
        $requestedAt = new DateTimeImmutable('2026-08-11 00:00:00+09:00');
        $reviewedAt = new DateTimeImmutable('2026-08-12 00:00:00+09:00');
        $request = new AccountCategoryChangeRequest(
            $requestIdentifier,
            $accountIdentifier,
            AccountCategory::GENERAL,
            AccountCategory::AGENCY,
            AccountCategoryChangeRequestStatus::APPROVED,
            $requestedAt,
            $reviewedBy,
            $reviewedAt,
            null,
        );

        $output = new ApproveAccountCategoryChangeRequestOutput();
        $output->setRequest($request);

        $this->assertSame([
            'requestIdentifier' => (string) $requestIdentifier,
            'accountIdentifier' => (string) $accountIdentifier,
            'currentAccountCategory' => AccountCategory::GENERAL->value,
            'requestedAccountCategory' => AccountCategory::AGENCY->value,
            'status' => AccountCategoryChangeRequestStatus::APPROVED->value,
            'requestedAt' => $requestedAt->format(DateTimeImmutable::ATOM),
            'reviewedBy' => (string) $reviewedBy,
            'reviewedAt' => $reviewedAt->format(DateTimeImmutable::ATOM),
            'rejectionReason' => null,
        ], $output->toArray());
    }

    public function testToArrayWithoutRequest(): void
    {
        $output = new ApproveAccountCategoryChangeRequestOutput();

        $this->assertSame([], $output->toArray());
    }
}
