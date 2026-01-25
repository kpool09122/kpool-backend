<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Domain\Entity\PromotionHistory;
use Source\Wiki\Principal\Domain\ValueObject\PromotionHistoryIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class PromotionHistoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $id = new PromotionHistoryIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $fromRole = 'editor';
        $toRole = 'approver';
        $reason = '貢献ポイントが基準を満たしたため';
        $processedAt = new DateTimeImmutable();

        $promotionHistory = new PromotionHistory(
            $id,
            $principalIdentifier,
            $fromRole,
            $toRole,
            $reason,
            $processedAt,
        );

        $this->assertSame($id, $promotionHistory->id());
        $this->assertSame($principalIdentifier, $promotionHistory->principalIdentifier());
        $this->assertSame($fromRole, $promotionHistory->fromRole());
        $this->assertSame($toRole, $promotionHistory->toRole());
        $this->assertSame($reason, $promotionHistory->reason());
        $this->assertSame($processedAt, $promotionHistory->processedAt());
    }

    /**
     * 正常系: reasonがnullでもインスタンスが正しく作成できること.
     *
     * @return void
     */
    public function test__constructWithNullReason(): void
    {
        $id = new PromotionHistoryIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $fromRole = 'approver';
        $toRole = 'editor';
        $reason = null;
        $processedAt = new DateTimeImmutable();

        $promotionHistory = new PromotionHistory(
            $id,
            $principalIdentifier,
            $fromRole,
            $toRole,
            $reason,
            $processedAt,
        );

        $this->assertSame($id, $promotionHistory->id());
        $this->assertSame($principalIdentifier, $promotionHistory->principalIdentifier());
        $this->assertSame($fromRole, $promotionHistory->fromRole());
        $this->assertSame($toRole, $promotionHistory->toRole());
        $this->assertNull($promotionHistory->reason());
        $this->assertSame($processedAt, $promotionHistory->processedAt());
    }
}
