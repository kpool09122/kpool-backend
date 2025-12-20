<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Talent\Domain\Entity\TalentHistory;
use Source\Wiki\Talent\Domain\ValueObject\TalentHistoryIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TalentHistoryTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスを作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $submitterIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $createTalentHistory = $this->createDummyTalentHistory(
            talentIdentifier: $talentIdentifier,
            submitterIdentifier: $submitterIdentifier,
        );

        $talentHistory = $createTalentHistory->talentHistory;

        $this->assertSame(
            (string)$createTalentHistory->historyIdentifier,
            (string)$talentHistory->historyIdentifier()
        );
        $this->assertSame(
            (string)$createTalentHistory->editorIdentifier,
            (string)$talentHistory->editorIdentifier()
        );
        $this->assertSame(
            (string)$createTalentHistory->submitterIdentifier,
            (string)$talentHistory->submitterIdentifier()
        );
        $this->assertSame(
            (string)$createTalentHistory->talentIdentifier,
            (string)$talentHistory->talentIdentifier()
        );
        $this->assertNull($createTalentHistory->draftTalentIdentifier);
        $this->assertSame(
            $createTalentHistory->fromStatus,
            $talentHistory->fromStatus()
        );
        $this->assertSame(
            $createTalentHistory->toStatus,
            $talentHistory->toStatus()
        );
        $this->assertSame(
            $createTalentHistory->recordedAt,
            $talentHistory->recordedAt()
        );
    }

    /**
     * 正常系: talentIdentifierのみがnullでも正しくインスタンスを作成できること.
     *
     * @return void
     */
    public function testConstructWithOnlyTalentIdentifierNull(): void
    {
        $draftTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $createTalentHistory = $this->createDummyTalentHistory(draftTalentIdentifier: $draftTalentIdentifier);

        $talentHistory = $createTalentHistory->talentHistory;

        $this->assertSame(
            (string)$createTalentHistory->historyIdentifier,
            (string)$talentHistory->historyIdentifier()
        );
        $this->assertSame(
            (string)$createTalentHistory->editorIdentifier,
            (string)$talentHistory->editorIdentifier()
        );
        $this->assertNull($createTalentHistory->talentIdentifier);
        $this->assertSame(
            (string)$createTalentHistory->draftTalentIdentifier,
            (string)$talentHistory->draftTalentIdentifier()
        );
        $this->assertSame(
            $createTalentHistory->fromStatus,
            $talentHistory->fromStatus()
        );
        $this->assertSame(
            $createTalentHistory->toStatus,
            $talentHistory->toStatus()
        );
        $this->assertSame(
            $createTalentHistory->recordedAt,
            $talentHistory->recordedAt()
        );
    }

    /**
     * 正常系: fromStatusがnullでも正しくインスタンスを作成できること.
     *
     * @return void
     */
    public function testConstructWithFromStatusNull(): void
    {
        $historyIdentifier = new TalentHistoryIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUlid());
        $toStatus = ApprovalStatus::Pending;
        $recordedAt = new DateTimeImmutable();

        $talentHistory = new TalentHistory(
            $historyIdentifier,
            $editorIdentifier,
            null,
            $talentIdentifier,
            null,
            null,
            $toStatus,
            $recordedAt,
        );

        $this->assertNull($talentHistory->fromStatus());
        $this->assertSame($toStatus, $talentHistory->toStatus());
    }

    /**
     * 異常系: TalentとDraftのどちらもNullの場合は例外がスローされること.
     *
     * @return void
     */
    public function testWhenBothTalentAndDraftAreNull(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one of talent identifier or draft identifier must be provided.');

        $historyIdentifier = new TalentHistoryIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $fromStatus = ApprovalStatus::Pending;
        $toStatus = ApprovalStatus::Rejected;
        $recordedAt = new DateTimeImmutable();

        new TalentHistory(
            $historyIdentifier,
            $editorIdentifier,
            null,
            null,
            null,
            $fromStatus,
            $toStatus,
            $recordedAt,
        );
    }

    /**
     * ダミーのTalentHistoryを作成するヘルパーメソッド
     *
     * @param ?TalentIdentifier $talentIdentifier
     * @param ?TalentIdentifier $draftTalentIdentifier
     * @param ?EditorIdentifier $submitterIdentifier
     * @return TalentHistoryTestData
     */
    private function createDummyTalentHistory(
        ?TalentIdentifier $talentIdentifier = null,
        ?TalentIdentifier $draftTalentIdentifier = null,
        ?EditorIdentifier $submitterIdentifier = null,
    ): TalentHistoryTestData {
        $historyIdentifier = new TalentHistoryIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $fromStatus = ApprovalStatus::Pending;
        $toStatus = ApprovalStatus::Approved;
        $recordedAt = new DateTimeImmutable();

        $talentHistory = new TalentHistory(
            $historyIdentifier,
            $editorIdentifier,
            $submitterIdentifier,
            $talentIdentifier,
            $draftTalentIdentifier,
            $fromStatus,
            $toStatus,
            $recordedAt,
        );

        return new TalentHistoryTestData(
            historyIdentifier: $historyIdentifier,
            editorIdentifier: $editorIdentifier,
            submitterIdentifier: $submitterIdentifier,
            talentIdentifier: $talentIdentifier,
            draftTalentIdentifier: $draftTalentIdentifier,
            fromStatus: $fromStatus,
            toStatus: $toStatus,
            recordedAt: $recordedAt,
            talentHistory: $talentHistory,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class TalentHistoryTestData
{
    public function __construct(
        public TalentHistoryIdentifier $historyIdentifier,
        public EditorIdentifier        $editorIdentifier,
        public ?EditorIdentifier       $submitterIdentifier,
        public ?TalentIdentifier       $talentIdentifier,
        public ?TalentIdentifier       $draftTalentIdentifier,
        public ApprovalStatus          $fromStatus,
        public ApprovalStatus          $toStatus,
        public DateTimeImmutable       $recordedAt,
        public TalentHistory           $talentHistory,
    ) {
    }
}
