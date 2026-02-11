<?php

declare(strict_types=1);

namespace Tests\Wiki\Grading\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Grading\Domain\Entity\ContributionPointHistory;
use Source\Wiki\Grading\Domain\ValueObject\ContributionPointHistoryIdentifier;
use Source\Wiki\Grading\Domain\ValueObject\ContributorType;
use Source\Wiki\Grading\Domain\ValueObject\Point;
use Source\Wiki\Grading\Domain\ValueObject\YearMonth;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;

class ContributionPointHistoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成できること（新規作成）.
     */
    public function test__constructForNewCreation(): void
    {
        $id = new ContributionPointHistoryIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $yearMonth = YearMonth::fromDateTime(new DateTimeImmutable('2025-01-15'));
        $points = new Point(Point::NEW_EDITOR);
        $resourceType = ResourceType::AGENCY;
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $roleType = ContributorType::EDITOR;
        $isNewCreation = true;
        $createdAt = new DateTimeImmutable();

        $history = new ContributionPointHistory(
            $id,
            $principalIdentifier,
            $yearMonth,
            $points,
            $resourceType,
            $wikiIdentifier,
            $roleType,
            $isNewCreation,
            $createdAt,
        );

        $this->assertSame($id, $history->id());
        $this->assertSame($principalIdentifier, $history->principalIdentifier());
        $this->assertSame($yearMonth, $history->yearMonth());
        $this->assertEquals($points, $history->points());
        $this->assertSame($resourceType, $history->resourceType());
        $this->assertSame($wikiIdentifier, $history->wikiIdentifier());
        $this->assertSame($roleType, $history->contributorType());
        $this->assertTrue($history->isNewCreation());
        $this->assertSame($createdAt, $history->createdAt());
    }

    /**
     * 正常系: インスタンスが正しく作成できること（更新）.
     */
    public function test__constructForUpdate(): void
    {
        $id = new ContributionPointHistoryIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $yearMonth = YearMonth::fromDateTime(new DateTimeImmutable('2025-02-20'));
        $points = new Point(Point::UPDATE_APPROVER);
        $resourceType = ResourceType::TALENT;
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $roleType = ContributorType::APPROVER;
        $isNewCreation = false;
        $createdAt = new DateTimeImmutable();

        $history = new ContributionPointHistory(
            $id,
            $principalIdentifier,
            $yearMonth,
            $points,
            $resourceType,
            $wikiIdentifier,
            $roleType,
            $isNewCreation,
            $createdAt,
        );

        $this->assertSame($id, $history->id());
        $this->assertSame($principalIdentifier, $history->principalIdentifier());
        $this->assertSame($yearMonth, $history->yearMonth());
        $this->assertEquals($points, $history->points());
        $this->assertSame($resourceType, $history->resourceType());
        $this->assertSame($wikiIdentifier, $history->wikiIdentifier());
        $this->assertSame($roleType, $history->contributorType());
        $this->assertFalse($history->isNewCreation());
        $this->assertSame($createdAt, $history->createdAt());
    }
}
