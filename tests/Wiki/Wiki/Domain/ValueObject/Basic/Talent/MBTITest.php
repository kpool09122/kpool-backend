<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Basic\Talent;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\MBTI;

class MBTITest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'INTJ' => 'INTJ',
            'INTP' => 'INTP',
            'ENTJ' => 'ENTJ',
            'ENTP' => 'ENTP',
            'INFJ' => 'INFJ',
            'INFP' => 'INFP',
            'ENFJ' => 'ENFJ',
            'ENFP' => 'ENFP',
            'ISTJ' => 'ISTJ',
            'ISFJ' => 'ISFJ',
            'ESTJ' => 'ESTJ',
            'ESFJ' => 'ESFJ',
            'ISTP' => 'ISTP',
            'ISFP' => 'ISFP',
            'ESTP' => 'ESTP',
            'ESFP' => 'ESFP',
        ], array_column(MBTI::cases(), 'value', 'name'));
    }
}
