<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Infrastructure\Factory;

use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Infrastructure\Factory\WikiDailySummaryFactory;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\AnalyzeWikiMetrics;

class WikiDailySummaryFactoryTest extends TestCase
{
    public function testFactoryCreatesAnIdentifiedSummaryWithEveryRequiredValue(): void
    {
        $day = new UtcDate('2020-01-01');
        $values = AnalyzeWikiMetrics::analysisValues($day);
        $identifier = '69200000-0000-7000-8000-000000000099';
        $uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $uuidGenerator->expects(self::once())->method('generate')->willReturn($identifier);
        $summary = (new WikiDailySummaryFactory($uuidGenerator))->create(new WikiIdentifier('69200000-0000-7000-8000-000000000001'), new TranslationSetIdentifier('69200000-0000-7000-8000-000000000002'), Language::JAPANESE, CountryCode::JAPAN, $values[0], $values[1], $values[2], $values[3], $values[4]);
        self::assertSame($identifier, (string) $summary->identifier());
        self::assertSame($day, $summary->day());
        self::assertSame($values[0], $summary->views());
        self::assertSame($values[1], $summary->viewers());
        self::assertSame($values[2], $summary->engagementTime());
        self::assertSame($values[3], $summary->submissions());
        self::assertSame($values[4], $summary->publications());
    }
}
