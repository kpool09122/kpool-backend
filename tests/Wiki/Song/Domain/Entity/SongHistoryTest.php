<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Song\Domain\Entity\SongHistory;
use Source\Wiki\Song\Domain\ValueObject\SongHistoryIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
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
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $submitterIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
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
            (string)$createSongHistory->subjectName,
            (string)$songHistory->subjectName()
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
        $draftSongIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
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
        $historyIdentifier = new SongHistoryIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $toStatus = ApprovalStatus::Pending;
        $subjectName = new SongName('Dynamite');
        $recordedAt = new DateTimeImmutable();

        $songHistory = new SongHistory(
            $historyIdentifier,
            $editorIdentifier,
            null,
            $songIdentifier,
            null,
            null,
            $toStatus,
            $subjectName,
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

        $historyIdentifier = new SongHistoryIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $fromStatus = ApprovalStatus::Pending;
        $toStatus = ApprovalStatus::Rejected;
        $subjectName = new SongName('Dynamite');
        $recordedAt = new DateTimeImmutable();

        new SongHistory(
            $historyIdentifier,
            $editorIdentifier,
            null,
            null,
            null,
            $fromStatus,
            $toStatus,
            $subjectName,
            $recordedAt,
        );
    }

    /**
     * ダミーのSongHistoryを作成するヘルパーメソッド
     *
     * @param ?SongIdentifier $songIdentifier
     * @param ?SongIdentifier $draftSongIdentifier
     * @param ?PrincipalIdentifier $submitterIdentifier
     * @return SongHistoryTestData
     */
    private function createDummySongHistory(
        ?SongIdentifier $songIdentifier = null,
        ?SongIdentifier $draftSongIdentifier = null,
        ?PrincipalIdentifier $submitterIdentifier = null,
    ): SongHistoryTestData {
        $historyIdentifier = new SongHistoryIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $fromStatus = ApprovalStatus::Pending;
        $toStatus = ApprovalStatus::Approved;
        $subjectName = new SongName('Dynamite');
        $recordedAt = new DateTimeImmutable();

        $songHistory = new SongHistory(
            $historyIdentifier,
            $editorIdentifier,
            $submitterIdentifier,
            $songIdentifier,
            $draftSongIdentifier,
            $fromStatus,
            $toStatus,
            $subjectName,
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
            subjectName: $subjectName,
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
        public PrincipalIdentifier   $editorIdentifier,
        public ?PrincipalIdentifier  $submitterIdentifier,
        public ?SongIdentifier       $songIdentifier,
        public ?SongIdentifier       $draftSongIdentifier,
        public ApprovalStatus        $fromStatus,
        public ApprovalStatus        $toStatus,
        public SongName              $subjectName,
        public DateTimeImmutable     $recordedAt,
        public SongHistory           $songHistory,
    ) {
    }
}
