<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiRejectionReason;

class DraftWikiRejectionReasonTest extends TestCase
{
    public function testCanCreate(): void
    {
        $reason = new DraftWikiRejectionReason('内容が不十分です');

        $this->assertSame('内容が不十分です', (string) $reason);
    }

    public function testAllows1000Characters(): void
    {
        $value = str_repeat('あ', DraftWikiRejectionReason::MAX_LENGTH);

        $reason = new DraftWikiRejectionReason($value);

        $this->assertSame($value, (string) $reason);
    }

    public function testThrowsWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Draft wiki rejection reason cannot be empty.');

        new DraftWikiRejectionReason('');
    }

    public function testThrowsWhenBlank(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Draft wiki rejection reason cannot be empty.');

        new DraftWikiRejectionReason('   ');
    }

    public function testThrowsWhenLongerThan1000Characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Draft wiki rejection reason cannot exceed 1000 characters.');

        new DraftWikiRejectionReason(str_repeat('あ', DraftWikiRejectionReason::MAX_LENGTH + 1));
    }
}
