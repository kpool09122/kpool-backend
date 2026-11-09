<?php

declare(strict_types=1);

namespace Tests\Analyze\App\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Analyze\App\Domain\ValueObject\AppCountryMetrics;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Shared\Domain\ValueObject\CountryCode;
use Tests\Helper\AnalyzeAppMetrics;

class AppCountryMetricsTest extends TestCase
{
    public function testUnknownAndKnownCountriesKeepTheirOwnCountsAndSnapshot(): void
    {
        [$users, $registered] = AnalyzeAppMetrics::analysisValues(new UtcDate('2020-01-01'));
        $unknown = new AppCountryMetrics(null, $users, $registered);
        $japan = new AppCountryMetrics(CountryCode::JAPAN, $users, $registered);
        self::assertSame('', $unknown->countryKey());
        self::assertSame('JP', $japan->countryKey());
        self::assertSame($users, $japan->applicationUsers);
        self::assertSame($registered, $japan->registeredUsers);
    }
}
