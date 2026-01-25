<?php

declare(strict_types=1);

namespace Tests\Wiki\Grading\Infrastructure\Factory;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Wiki\Grading\Domain\Facotory\ContributionPointSummaryFactoryInterface;
use Source\Wiki\Grading\Domain\ValueObject\Point;
use Source\Wiki\Grading\Domain\ValueObject\YearMonth;
use Source\Wiki\Grading\Infrastructure\Factory\ContributionPointSummaryFactory;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ContributionPointSummaryFactoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(ContributionPointSummaryFactoryInterface::class);
        $this->assertInstanceOf(ContributionPointSummaryFactory::class, $factory);
    }

    /**
     * 正常系: ContributionPointSummaryが正しく作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $yearMonth = YearMonth::fromDateTime(new DateTimeImmutable());
        $points = new Point(100);

        $factory = $this->app->make(ContributionPointSummaryFactoryInterface::class);
        $summary = $factory->create(
            $principalIdentifier,
            $yearMonth,
            $points,
        );

        $this->assertTrue(UuidValidator::isValid((string) $summary->id()));
        $this->assertSame($principalIdentifier, $summary->principalIdentifier());
        $this->assertSame($yearMonth, $summary->yearMonth());
        $this->assertEquals($points, $summary->points());
        $this->assertNotNull($summary->createdAt());
        $this->assertNotNull($summary->updatedAt());
    }
}
