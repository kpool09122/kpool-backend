<?php

declare(strict_types=1);

namespace Tests\Monetization\Settlement\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Settlement\Domain\ValueObject\TransferStatus;

class TransferStatusTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'PENDING' => 'pending',
            'SENT' => 'sent',
            'FAILED' => 'failed',
        ], array_column(TransferStatus::cases(), 'value', 'name'));
    }
}
