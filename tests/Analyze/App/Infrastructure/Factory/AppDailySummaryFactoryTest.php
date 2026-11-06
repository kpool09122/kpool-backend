<?php

declare(strict_types=1);

namespace Tests\Analyze\App\Infrastructure\Factory;

use PHPUnit\Framework\TestCase;
use Source\Analyze\App\Infrastructure\Factory\AppDailySummaryFactory;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Tests\Helper\AnalyzeAppMetrics;

class AppDailySummaryFactoryTest extends TestCase
{
    public function testFactoryCreatesAnIdentifiedSummaryWithEveryRequiredValue(): void
    {
        $day = new UtcDate('2020-01-01');
        $values = AnalyzeAppMetrics::analysisValues($day);
        $identifier = '69200000-0000-7000-8000-000000000099';
        $uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $uuidGenerator->expects(self::once())->method('generate')->willReturn($identifier);
        $summary = (new AppDailySummaryFactory($uuidGenerator))->create($values[0], $values[1]);
        self::assertSame($identifier, (string) $summary->identifier());
        self::assertSame($day, $summary->day());
        self::assertSame($values[0], $summary->applicationUsers());
        self::assertSame($values[1], $summary->registeredUsers());
    }
}
