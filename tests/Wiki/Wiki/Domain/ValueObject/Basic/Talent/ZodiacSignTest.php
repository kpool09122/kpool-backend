<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Basic\Talent;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\ZodiacSign;

class ZodiacSignTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'ARIES' => 'aries',
            'TAURUS' => 'taurus',
            'GEMINI' => 'gemini',
            'CANCER' => 'cancer',
            'LEO' => 'leo',
            'VIRGO' => 'virgo',
            'LIBRA' => 'libra',
            'SCORPIO' => 'scorpio',
            'SAGITTARIUS' => 'sagittarius',
            'CAPRICORN' => 'capricorn',
            'AQUARIUS' => 'aquarius',
            'PISCES' => 'pisces',
        ], array_column(ZodiacSign::cases(), 'value', 'name'));
    }
}
