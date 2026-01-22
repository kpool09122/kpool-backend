<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Domain\Entity\ContributionPointSummary;
use Source\Wiki\Principal\Domain\ValueObject\ContributionPointSummaryIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\Point;
use Source\Wiki\Principal\Domain\ValueObject\YearMonth;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class ContributionPointSummaryTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $id = new ContributionPointSummaryIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $yearMonth = YearMonth::fromDateTime(new DateTimeImmutable('2025-01-15'));
        $points = new Point(100);
        $createdAt = new DateTimeImmutable();
        $updatedAt = new DateTimeImmutable();

        $summary = new ContributionPointSummary(
            $id,
            $principalIdentifier,
            $yearMonth,
            $points,
            $createdAt,
            $updatedAt,
        );

        $this->assertSame($id, $summary->id());
        $this->assertSame($principalIdentifier, $summary->principalIdentifier());
        $this->assertSame($yearMonth, $summary->yearMonth());
        $this->assertSame($points, $summary->points());
        $this->assertSame($createdAt, $summary->createdAt());
        $this->assertSame($updatedAt, $summary->updatedAt());
    }

    /**
     * 正常系: setPointsでポイントが更新できること.
     *
     * @return void
     */
    public function testSetPoints(): void
    {
        $summary = $this->createContributionPointSummary(new Point(100));

        $newPoints = new Point(200);
        $summary->setPoints($newPoints);

        $this->assertSame($newPoints, $summary->points());
    }

    /**
     * 正常系: addPointsでポイントが加算できること.
     *
     * @return void
     */
    public function testAddPoints(): void
    {
        $initialPoints = new Point(100);
        $summary = $this->createContributionPointSummary($initialPoints);

        $addedPoints = new Point(50);
        $summary->addPoints($addedPoints);

        $this->assertSame(150, $summary->points()->value());
    }

    /**
     * 正常系: setUpdatedAtで更新日時が更新できること.
     *
     * @return void
     */
    public function testSetUpdatedAt(): void
    {
        $originalUpdatedAt = new DateTimeImmutable('2025-01-01');
        $summary = $this->createContributionPointSummary(updatedAt: $originalUpdatedAt);

        $newUpdatedAt = new DateTimeImmutable('2025-01-15');
        $summary->setUpdatedAt($newUpdatedAt);

        $this->assertSame($newUpdatedAt, $summary->updatedAt());
    }

    private function createContributionPointSummary(
        ?Point $points = null,
        ?DateTimeImmutable $updatedAt = null,
    ): ContributionPointSummary {
        return new ContributionPointSummary(
            new ContributionPointSummaryIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            YearMonth::fromDateTime(new DateTimeImmutable('2025-01-15')),
            $points ?? new Point(100),
            new DateTimeImmutable(),
            $updatedAt ?? new DateTimeImmutable(),
        );
    }
}
