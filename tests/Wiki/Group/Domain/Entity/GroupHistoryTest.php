<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Wiki\Group\Domain\Entity\GroupHistory;
use Source\Wiki\Group\Domain\ValueObject\GroupHistoryIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GroupHistoryTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスを作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $submitterIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $createGroupHistory = $this->createDummyGroupHistory(
            groupIdentifier: $groupIdentifier,
            submitterIdentifier: $submitterIdentifier,
        );

        $groupHistory = $createGroupHistory->groupHistory;

        $this->assertSame(
            (string)$createGroupHistory->historyIdentifier,
            (string)$groupHistory->historyIdentifier()
        );
        $this->assertSame(
            (string)$createGroupHistory->editorIdentifier,
            (string)$groupHistory->editorIdentifier()
        );
        $this->assertSame(
            (string)$createGroupHistory->submitterIdentifier,
            (string)$groupHistory->submitterIdentifier()
        );
        $this->assertSame(
            (string)$createGroupHistory->groupIdentifier,
            (string)$groupHistory->groupIdentifier()
        );
        $this->assertNull($createGroupHistory->draftGroupIdentifier);
        $this->assertSame(
            $createGroupHistory->fromStatus,
            $groupHistory->fromStatus()
        );
        $this->assertSame(
            $createGroupHistory->toStatus,
            $groupHistory->toStatus()
        );
        $this->assertSame(
            (string)$createGroupHistory->subjectName,
            (string)$groupHistory->subjectName()
        );
        $this->assertSame(
            $createGroupHistory->recordedAt,
            $groupHistory->recordedAt()
        );
    }

    /**
     * 正常系: groupIdentifierのみがnullでも正しくインスタンスを作成できること.
     *
     * @return void
     */
    public function testConstructWithOnlyGroupIdentifierNull(): void
    {
        $draftGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $createGroupHistory = $this->createDummyGroupHistory(draftGroupIdentifier: $draftGroupIdentifier);

        $groupHistory = $createGroupHistory->groupHistory;

        $this->assertSame(
            (string)$createGroupHistory->historyIdentifier,
            (string)$groupHistory->historyIdentifier()
        );
        $this->assertSame(
            (string)$createGroupHistory->editorIdentifier,
            (string)$groupHistory->editorIdentifier()
        );
        $this->assertNull($createGroupHistory->groupIdentifier);
        $this->assertSame(
            (string)$createGroupHistory->draftGroupIdentifier,
            (string)$groupHistory->draftGroupIdentifier()
        );
        $this->assertSame(
            $createGroupHistory->fromStatus,
            $groupHistory->fromStatus()
        );
        $this->assertSame(
            $createGroupHistory->toStatus,
            $groupHistory->toStatus()
        );
        $this->assertSame(
            $createGroupHistory->recordedAt,
            $groupHistory->recordedAt()
        );
    }

    /**
     * 正常系: fromStatusがnullでも正しくインスタンスを作成できること.
     *
     * @return void
     */
    public function testConstructWithFromStatusNull(): void
    {
        $historyIdentifier = new GroupHistoryIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $toStatus = ApprovalStatus::Pending;
        $subjectName = new GroupName('TWICE');
        $recordedAt = new DateTimeImmutable();

        $groupHistory = new GroupHistory(
            $historyIdentifier,
            $editorIdentifier,
            null,
            $groupIdentifier,
            null,
            null,
            $toStatus,
            $subjectName,
            $recordedAt,
        );

        $this->assertNull($groupHistory->fromStatus());
        $this->assertSame($toStatus, $groupHistory->toStatus());
    }

    /**
     * 異常系: GroupとDraftのどちらもNullの場合は例外がスローされること.
     *
     * @return void
     */
    public function testWhenBothGroupAndDraftAreNull(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $historyIdentifier = new GroupHistoryIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $fromStatus = ApprovalStatus::Pending;
        $toStatus = ApprovalStatus::Rejected;
        $subjectName = new GroupName('TWICE');
        $recordedAt = new DateTimeImmutable();

        new GroupHistory(
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
     * ダミーのGroupHistoryを作成するヘルパーメソッド
     *
     * @param ?GroupIdentifier $groupIdentifier
     * @param ?GroupIdentifier $draftGroupIdentifier
     * @param ?EditorIdentifier $submitterIdentifier
     * @return GroupHistoryTestData
     */
    private function createDummyGroupHistory(
        ?GroupIdentifier $groupIdentifier = null,
        ?GroupIdentifier $draftGroupIdentifier = null,
        ?EditorIdentifier $submitterIdentifier = null,
    ): GroupHistoryTestData {
        $historyIdentifier = new GroupHistoryIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $fromStatus = ApprovalStatus::Pending;
        $toStatus = ApprovalStatus::Approved;
        $subjectName = new GroupName('TWICE');
        $recordedAt = new DateTimeImmutable();

        $groupHistory = new GroupHistory(
            $historyIdentifier,
            $editorIdentifier,
            $submitterIdentifier,
            $groupIdentifier,
            $draftGroupIdentifier,
            $fromStatus,
            $toStatus,
            $subjectName,
            $recordedAt,
        );

        return new GroupHistoryTestData(
            historyIdentifier: $historyIdentifier,
            editorIdentifier: $editorIdentifier,
            submitterIdentifier: $submitterIdentifier,
            groupIdentifier: $groupIdentifier,
            draftGroupIdentifier: $draftGroupIdentifier,
            fromStatus: $fromStatus,
            toStatus: $toStatus,
            subjectName: $subjectName,
            recordedAt: $recordedAt,
            groupHistory: $groupHistory,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class GroupHistoryTestData
{
    public function __construct(
        public GroupHistoryIdentifier $historyIdentifier,
        public EditorIdentifier       $editorIdentifier,
        public ?EditorIdentifier      $submitterIdentifier,
        public ?GroupIdentifier       $groupIdentifier,
        public ?GroupIdentifier       $draftGroupIdentifier,
        public ApprovalStatus         $fromStatus,
        public ApprovalStatus         $toStatus,
        public GroupName              $subjectName,
        public DateTimeImmutable      $recordedAt,
        public GroupHistory           $groupHistory,
    ) {
    }
}
