<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotStatus;

class AccountSnapshotStatusTest extends TestCase
{
    public function testReviewStatuses(): void
    {
        self::assertSame(AccountSnapshotStatus::APPROVED, AccountSnapshotStatus::from('approved'));
        self::assertSame(AccountSnapshotStatus::REJECTED, AccountSnapshotStatus::from('rejected'));
        self::assertNull(AccountSnapshotStatus::tryFrom('pending'));
    }
}
