<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Wiki\Group\Domain\Entity\GroupHistory;
use Source\Wiki\Group\Domain\ValueObject\GroupHistoryIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
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
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $submitterIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
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
        $draftGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
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
        $historyIdentifier = new GroupHistoryIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $toStatus = ApprovalStatus::Pending;
        $subjectName = new GroupName('TWICE');
        $recordedAt = new DateTimeImmutable();

        $groupHistory = new GroupHistory(
            $historyIdentifier,
            HistoryActionType::DraftStatusChange,
            $editorIdentifier,
            null,
            $groupIdentifier,
            null,
            null,
            $toStatus,
            null,
            null,
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

        $historyIdentifier = new GroupHistoryIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $fromStatus = ApprovalStatus::Pending;
        $toStatus = ApprovalStatus::Rejected;
        $subjectName = new GroupName('TWICE');
        $recordedAt = new DateTimeImmutable();

        new GroupHistory(
            $historyIdentifier,
            HistoryActionType::DraftStatusChange,
            $editorIdentifier,
            null,
            null,
            null,
            $fromStatus,
            $toStatus,
            null,
            null,
            $subjectName,
            $recordedAt,
        );
    }

    /**
     * ダミーのGroupHistoryを作成するヘルパーメソッド
     *
     * @param ?GroupIdentifier $groupIdentifier
     * @param ?GroupIdentifier $draftGroupIdentifier
     * @param ?PrincipalIdentifier $submitterIdentifier
     * @param HistoryActionType $actionType
     * @param ?Version $fromVersion
     * @param ?Version $toVersion
     * @return GroupHistoryTestData
     */
    private function createDummyGroupHistory(
        ?GroupIdentifier $groupIdentifier = null,
        ?GroupIdentifier $draftGroupIdentifier = null,
        ?PrincipalIdentifier $submitterIdentifier = null,
        HistoryActionType $actionType = HistoryActionType::DraftStatusChange,
        ?Version $fromVersion = null,
        ?Version $toVersion = null,
    ): GroupHistoryTestData {
        $historyIdentifier = new GroupHistoryIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $fromStatus = ApprovalStatus::Pending;
        $toStatus = ApprovalStatus::Approved;
        $subjectName = new GroupName('TWICE');
        $recordedAt = new DateTimeImmutable();

        $groupHistory = new GroupHistory(
            $historyIdentifier,
            $actionType,
            $editorIdentifier,
            $submitterIdentifier,
            $groupIdentifier,
            $draftGroupIdentifier,
            $fromStatus,
            $toStatus,
            $fromVersion,
            $toVersion,
            $subjectName,
            $recordedAt,
        );

        return new GroupHistoryTestData(
            historyIdentifier: $historyIdentifier,
            actionType: $actionType,
            editorIdentifier: $editorIdentifier,
            submitterIdentifier: $submitterIdentifier,
            groupIdentifier: $groupIdentifier,
            draftGroupIdentifier: $draftGroupIdentifier,
            fromStatus: $fromStatus,
            toStatus: $toStatus,
            fromVersion: $fromVersion,
            toVersion: $toVersion,
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
        public HistoryActionType      $actionType,
        public PrincipalIdentifier    $editorIdentifier,
        public ?PrincipalIdentifier   $submitterIdentifier,
        public ?GroupIdentifier       $groupIdentifier,
        public ?GroupIdentifier       $draftGroupIdentifier,
        public ApprovalStatus         $fromStatus,
        public ApprovalStatus         $toStatus,
        public ?Version               $fromVersion,
        public ?Version               $toVersion,
        public GroupName              $subjectName,
        public DateTimeImmutable      $recordedAt,
        public GroupHistory           $groupHistory,
    ) {
    }
}
