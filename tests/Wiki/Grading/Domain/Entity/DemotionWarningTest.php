<?php

declare(strict_types=1);

namespace Tests\Wiki\Grading\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Grading\Domain\Entity\DemotionWarning;
use Source\Wiki\Grading\Domain\ValueObject\DemotionWarningIdentifier;
use Source\Wiki\Grading\Domain\ValueObject\WarningCount;
use Source\Wiki\Grading\Domain\ValueObject\YearMonth;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class DemotionWarningTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $id = new DemotionWarningIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $warningCount = new WarningCount(1);
        $lastWarningMonth = YearMonth::fromDateTime(new DateTimeImmutable('2025-01-15'));
        $createdAt = new DateTimeImmutable();
        $updatedAt = new DateTimeImmutable();

        $demotionWarning = new DemotionWarning(
            $id,
            $principalIdentifier,
            $warningCount,
            $lastWarningMonth,
            $createdAt,
            $updatedAt,
        );

        $this->assertSame($id, $demotionWarning->id());
        $this->assertSame($principalIdentifier, $demotionWarning->principalIdentifier());
        $this->assertSame($warningCount, $demotionWarning->warningCount());
        $this->assertSame($lastWarningMonth, $demotionWarning->lastWarningMonth());
        $this->assertSame($createdAt, $demotionWarning->createdAt());
        $this->assertSame($updatedAt, $demotionWarning->updatedAt());
    }

    /**
     * 正常系: incrementWarningCountで警告回数がインクリメントされること.
     *
     * @return void
     */
    public function testIncrementWarningCount(): void
    {
        $demotionWarning = $this->createDemotionWarning(new WarningCount(1));

        $demotionWarning->incrementWarningCount();

        $this->assertSame(2, $demotionWarning->warningCount()->value());
    }

    /**
     * 正常系: resetWarningCountで警告回数が0にリセットされること.
     *
     * @return void
     */
    public function testResetWarningCount(): void
    {
        $demotionWarning = $this->createDemotionWarning(new WarningCount(2));

        $demotionWarning->resetWarningCount();

        $this->assertSame(0, $demotionWarning->warningCount()->value());
    }

    /**
     * 正常系: setLastWarningMonthで最終警告月が更新されること.
     *
     * @return void
     */
    public function testSetLastWarningMonth(): void
    {
        $originalLastWarningMonth = YearMonth::fromDateTime(new DateTimeImmutable('2025-01-15'));
        $demotionWarning = $this->createDemotionWarning(lastWarningMonth: $originalLastWarningMonth);

        $newLastWarningMonth = YearMonth::fromDateTime(new DateTimeImmutable('2025-02-15'));
        $demotionWarning->setLastWarningMonth($newLastWarningMonth);

        $this->assertSame($newLastWarningMonth, $demotionWarning->lastWarningMonth());
    }

    /**
     * 正常系: setUpdatedAtで更新日時が更新されること.
     *
     * @return void
     */
    public function testSetUpdatedAt(): void
    {
        $originalUpdatedAt = new DateTimeImmutable('2025-01-01');
        $demotionWarning = $this->createDemotionWarning(updatedAt: $originalUpdatedAt);

        $newUpdatedAt = new DateTimeImmutable('2025-01-15');
        $demotionWarning->setUpdatedAt($newUpdatedAt);

        $this->assertSame($newUpdatedAt, $demotionWarning->updatedAt());
    }

    private function createDemotionWarning(
        ?WarningCount $warningCount = null,
        ?YearMonth $lastWarningMonth = null,
        ?DateTimeImmutable $updatedAt = null,
    ): DemotionWarning {
        return new DemotionWarning(
            new DemotionWarningIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            $warningCount ?? new WarningCount(1),
            $lastWarningMonth ?? YearMonth::fromDateTime(new DateTimeImmutable('2025-01-15')),
            new DateTimeImmutable(),
            $updatedAt ?? new DateTimeImmutable(),
        );
    }
}
