<?php

declare(strict_types=1);

namespace Tests\Analyze\Shared\Infrastructure\Service;

use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Infrastructure\Service\RegionNormalizer;
use Source\Shared\Domain\ValueObject\CountryCode;

class RegionNormalizerTest extends TestCase
{
    public function testNormalizesGa4NamesOutsideTheMapEnum(): void
    {
        $normalizer = new RegionNormalizer();
        self::assertSame('DE-BE', (string) $normalizer->normalize(CountryCode::GERMANY, 'Berlin'));
        self::assertSame('JP-13', (string) $normalizer->normalize(CountryCode::JAPAN, 'Tokyo'));
        self::assertSame('US-CA', (string) $normalizer->normalize(CountryCode::UNITED_STATES, 'California'));
        self::assertNull($normalizer->normalize(CountryCode::JAPAN, 'California'));
        self::assertNull($normalizer->normalize(CountryCode::JAPAN, '(not set)'));
        self::assertNull($normalizer->normalize(CountryCode::JAPAN, 'unknown future name'));
        self::assertSame('CN-11', (string) $normalizer->normalize(CountryCode::CHINA, 'Beijing'));
        self::assertSame('KR-11', (string) $normalizer->normalize(CountryCode::KOREA_REPUBLIC, 'Seoul'));
        self::assertSame('TW-HSZ', (string) $normalizer->normalize(CountryCode::TAIWAN, 'Hsinchu City'));
        self::assertSame('TW-HSQ', (string) $normalizer->normalize(CountryCode::TAIWAN, 'Hsinchu County'));
        self::assertSame('PH-00', (string) $normalizer->normalize(CountryCode::PHILIPPINES, 'Metro Manila'));
    }
}
