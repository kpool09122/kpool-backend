<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyType;
use ValueError;

class ReplyTypeTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'OPERATOR' => 'operator',
            'SUBMITTER' => 'submitter',
        ], array_column(ReplyType::cases(), 'value', 'name'));
        $this->assertSame(ReplyType::OPERATOR, ReplyType::from('operator'));
        $this->assertSame(ReplyType::SUBMITTER, ReplyType::from('submitter'));
    }

    public function testUnknownValueIsRejected(): void
    {
        $this->expectException(ValueError::class);

        ReplyType::from('unknown');
    }
}
