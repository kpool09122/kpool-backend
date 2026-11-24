<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeRequestStatus;

class AccountSnapshotChangeRequestStatusTest extends TestCase
{
    public function testPersistedStates(): void
    {
        self::assertSame(['pending','approved','rejected'], array_map(static fn (AccountSnapshotChangeRequestStatus $status): string => $status->value, AccountSnapshotChangeRequestStatus::cases()));
    }
}
