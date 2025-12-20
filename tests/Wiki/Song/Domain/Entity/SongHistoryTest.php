<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Song\Domain\Entity\SongHistory;
use Source\Wiki\Song\Domain\ValueObject\SongHistoryIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SongHistoryTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスを作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $submitterIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $createSongHistory = $this->createDummySongHistory(
            songIdentifier: $songIdentifier,
            submitterIdentifier: $submitterIdentifier,
        );

        $songHistory = $createSongHistory->songHistory;

        $this->assertSame(
            (string)$createSongHistory->historyIdentifier,
            (string)$songHistory->historyIdentifier()
        );
        $this->assertSame(
            (string)$createSongHistory->editorIdentifier,
            (string)$songHistory->editorIdentifier()
        );
        $this->assertSame(
            (string)$createSongHistory->submitterIdentifier,
            (string)$songHistory->submitterIdentifier()
        );
        $this->assertSame(
            (string)$createSongHistory->songIdentifier,
            (string)$songHistory->songIdentifier()
        );
        $this->assertNull($createSongHistory->draftSongIdentifier);
        $this->assertSame(
            $createSongHistory->fromStatus,
            $songHistory->fromStatus()
        );
        $this->assertSame(
            $createSongHistory->toStatus,
            $songHistory->toStatus()
        );
        $this->assertSame(
            $createSongHistory->recordedAt,
            $songHistory->recordedAt()
        );
    }

    /**
     * 正常系: songIdentifierのみがnullでも正しくインスタンスを作成できること.
     *
     * @return void
     */
    public function testConstructWithOnlySongIdentifierNull(): void
    {
        $draftSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $createSongHistory = $this->createDummySongHistory(draftSongIdentifier: $draftSongIdentifier);

        $songHistory = $createSongHistory->songHistory;

        $this->assertSame(
            (string)$createSongHistory->historyIdentifier,
            (string)$songHistory->historyIdentifier()
        );
        $this->assertSame(
            (string)$createSongHistory->editorIdentifier,
            (string)$songHistory->editorIdentifier()
        );
        $this->assertNull($createSongHistory->songIdentifier);
        $this->assertSame(
            (string)$createSongHistory->draftSongIdentifier,
            (string)$songHistory->draftSongIdentifier()
        );
        $this->assertSame(
            $createSongHistory->fromStatus,
            $songHistory->fromStatus()
        );
        $this->assertSame(
            $createSongHistory->toStatus,
            $songHistory->toStatus()
        );
        $this->assertSame(
            $createSongHistory->recordedAt,
            $songHistory->recordedAt()
        );
    }

    /**
     * 正常系: fromStatusがnullでも正しくインスタンスを作成できること.
     *
     * @return void
     */
    public function testConstructWithFromStatusNull(): void
    {
        $historyIdentifier = new SongHistoryIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $toStatus = ApprovalStatus::Pending;
        $recordedAt = new DateTimeImmutable();

        $songHistory = new SongHistory(
            $historyIdentifier,
            $editorIdentifier,
            null,
            $songIdentifier,
            null,
            null,
            $toStatus,
            $recordedAt,
        );

        $this->assertNull($songHistory->fromStatus());
        $this->assertSame($toStatus, $songHistory->toStatus());
    }

    /**
     * 異常系: SongとDraftのどちらもNullの場合は例外がスローされること.
     *
     * @return void
     */
    public function testWhenBothSongAndDraftAreNull(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $historyIdentifier = new SongHistoryIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $fromStatus = ApprovalStatus::Pending;
        $toStatus = ApprovalStatus::Rejected;
        $recordedAt = new DateTimeImmutable();

        new SongHistory(
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
     * ダミーのSongHistoryを作成するヘルパーメソッド
     *
     * @param ?SongIdentifier $songIdentifier
     * @param ?SongIdentifier $draftSongIdentifier
     * @param ?EditorIdentifier $submitterIdentifier
     * @return SongHistoryTestData
     */
    private function createDummySongHistory(
        ?SongIdentifier $songIdentifier = null,
        ?SongIdentifier $draftSongIdentifier = null,
        ?EditorIdentifier $submitterIdentifier = null,
    ): SongHistoryTestData {
        $historyIdentifier = new SongHistoryIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $fromStatus = ApprovalStatus::Pending;
        $toStatus = ApprovalStatus::Approved;
        $recordedAt = new DateTimeImmutable();

        $songHistory = new SongHistory(
            $historyIdentifier,
            $editorIdentifier,
            $submitterIdentifier,
            $songIdentifier,
            $draftSongIdentifier,
            $fromStatus,
            $toStatus,
            $recordedAt,
        );

        return new SongHistoryTestData(
            historyIdentifier: $historyIdentifier,
            editorIdentifier: $editorIdentifier,
            submitterIdentifier: $submitterIdentifier,
            songIdentifier: $songIdentifier,
            draftSongIdentifier: $draftSongIdentifier,
            fromStatus: $fromStatus,
            toStatus: $toStatus,
            recordedAt: $recordedAt,
            songHistory: $songHistory,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class SongHistoryTestData
{
    public function __construct(
        public SongHistoryIdentifier $historyIdentifier,
        public EditorIdentifier      $editorIdentifier,
        public ?EditorIdentifier     $submitterIdentifier,
        public ?SongIdentifier       $songIdentifier,
        public ?SongIdentifier       $draftSongIdentifier,
        public ApprovalStatus        $fromStatus,
        public ApprovalStatus        $toStatus,
        public DateTimeImmutable     $recordedAt,
        public SongHistory           $songHistory,
    ) {
    }
}
