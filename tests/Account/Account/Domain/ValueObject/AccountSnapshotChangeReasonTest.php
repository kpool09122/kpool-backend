<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\ValueObject\AccountSnapshotChangeReason;

class AccountSnapshotChangeReasonTest extends TestCase
{
    public function testReasonPreservesMeaningfulText(): void
    {
        self::assertSame('住所変更', (string) new AccountSnapshotChangeReason('住所変更'));
        self::assertSame(str_repeat('あ', 2000), (string) new AccountSnapshotChangeReason(str_repeat('あ', 2000)));
    }

    public function testBlankReasonIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AccountSnapshotChangeReason('   ');
    }

    public function testUnicodeWhitespaceReasonIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AccountSnapshotChangeReason("　\u{00A0}\t");
    }

    public function testOverlongReasonIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AccountSnapshotChangeReason(str_repeat('あ', 2001));
    }
}
