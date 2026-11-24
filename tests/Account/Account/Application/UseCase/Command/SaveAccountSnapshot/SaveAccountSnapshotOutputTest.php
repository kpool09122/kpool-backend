<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\SaveAccountSnapshot;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Command\SaveAccountSnapshot\SaveAccountSnapshotOutput;
use Source\Account\Account\Domain\Entity\AccountSnapshot;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotIdentifier;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotStatus;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\CreateAccountSnapshotProfile;
use Tests\Helper\StrTestHelper;

class SaveAccountSnapshotOutputTest extends TestCase
{
    public function testOutputContract(): void
    {
        $output = new SaveAccountSnapshotOutput();
        self::assertSame([], $output->toArray());
        $sid = StrTestHelper::generateUuid();
        $aid = StrTestHelper::generateUuid();
        $time = new DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $snapshot = new AccountSnapshot(new AccountSnapshotIdentifier($sid), new AccountIdentifier($aid), AccountSnapshotStatus::APPROVED, CreateAccountSnapshotProfile::create(), [], $time);
        $output->setSnapshot($snapshot);
        self::assertSame(['snapshotIdentifier' => $sid,'accountIdentifier' => $aid,'status' => 'approved','savedAt' => $time->format('c')], $output->toArray());
    }
}
