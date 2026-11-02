<?php

declare(strict_types=1);

namespace Tests\Analyze\App\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Analyze\App\Domain\ValueObject\RegisteredUserSnapshot;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricProvenance;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Tests\Helper\AnalyzeAppMetrics;

class RegisteredUserSnapshotTest extends TestCase
{
    public function testRetainsItsBusinessValueAndProvenance(): void
    {
        $day = new UtcDate('2020-01-01');
        $origin = new MetricProvenance(AnalyzeAppMetrics::definition(Metric::REGISTERED_USERS), $day, AnalyzeAppMetrics::now(), ['sampled']);
        $value = '99999999999999999999';
        $result = new RegisteredUserSnapshot($value, $day->next()->instant(), $origin);
        self::assertSame($value, $result->count());
        self::assertSame($origin, $result->provenance());
        self::assertEquals($day->next()->instant(), $result->asOf());
    }

    /** @return iterable<string, array{string}> */
    public static function invalidValues(): iterable
    {
        foreach (['', '-1', '+1', '01', '1e3', ' 1', '1 ', '100000000000000000000', '1.5'] as $value) {
            yield 'invalid:' . $value => [$value];
        }
    }

    #[DataProvider('invalidValues')]
    public function testRejectsInvalidCounts(string $value): void
    {
        $day = new UtcDate('2020-01-01');
        $origin = new MetricProvenance(AnalyzeAppMetrics::definition(Metric::REGISTERED_USERS), $day, AnalyzeAppMetrics::now());
        $this->expectException(InvalidArgumentException::class);
        new RegisteredUserSnapshot($value, $day->next()->instant(), $origin);
    }

    public function testRejectsAnotherMetricDefinition(): void
    {
        $day = new UtcDate('2020-01-01');
        $origin = new MetricProvenance(AnalyzeAppMetrics::definition(Metric::APPLICATION_USERS), $day, AnalyzeAppMetrics::now());
        $value = '0';
        $this->expectException(InvalidArgumentException::class);
        new RegisteredUserSnapshot($value, $day->next()->instant(), $origin);
    }

    public function testRejectsASnapshotThatIsNotTheUtcClosingBalance(): void
    {
        $day = new UtcDate('2020-01-01');
        $origin = new MetricProvenance(AnalyzeAppMetrics::definition(Metric::REGISTERED_USERS), $day, AnalyzeAppMetrics::now());
        $this->expectException(InvalidArgumentException::class);
        new RegisteredUserSnapshot('7', $day->instant(), $origin);
    }
}
