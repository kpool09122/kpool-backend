<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\Entity\WikiHistory;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiHistoryIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;

class WikiHistoryTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスを作成できること
     */
    public function test__construct(): void
    {
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $submitterIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $testData = $this->createDummyWikiHistory(
            wikiIdentifier: $wikiIdentifier,
            submitterIdentifier: $submitterIdentifier,
        );

        $wikiHistory = $testData->wikiHistory;

        $this->assertSame(
            (string) $testData->historyIdentifier,
            (string) $wikiHistory->historyIdentifier()
        );
        $this->assertSame(
            $testData->actionType,
            $wikiHistory->actionType()
        );
        $this->assertSame(
            (string) $testData->actorIdentifier,
            (string) $wikiHistory->actorIdentifier()
        );
        $this->assertSame(
            (string) $testData->submitterIdentifier,
            (string) $wikiHistory->submitterIdentifier()
        );
        $this->assertSame(
            (string) $testData->wikiIdentifier,
            (string) $wikiHistory->wikiIdentifier()
        );
        $this->assertNull($testData->draftWikiIdentifier);
        $this->assertSame(
            $testData->fromStatus,
            $wikiHistory->fromStatus()
        );
        $this->assertSame(
            $testData->toStatus,
            $wikiHistory->toStatus()
        );
        $this->assertNull($wikiHistory->fromVersion());
        $this->assertNull($wikiHistory->toVersion());
        $this->assertSame(
            (string) $testData->subjectName,
            (string) $wikiHistory->subjectName()
        );
        $this->assertSame(
            $testData->recordedAt,
            $wikiHistory->recordedAt()
        );
    }

    /**
     * 正常系: wikiIdentifierのみがnullでも正しくインスタンスを作成できること
     */
    public function testConstructWithOnlyWikiIdentifierNull(): void
    {
        $draftWikiIdentifier = new DraftWikiIdentifier(StrTestHelper::generateUuid());
        $testData = $this->createDummyWikiHistory(draftWikiIdentifier: $draftWikiIdentifier);

        $wikiHistory = $testData->wikiHistory;

        $this->assertSame(
            (string) $testData->historyIdentifier,
            (string) $wikiHistory->historyIdentifier()
        );
        $this->assertSame(
            (string) $testData->actorIdentifier,
            (string) $wikiHistory->actorIdentifier()
        );
        $this->assertNull($testData->wikiIdentifier);
        $this->assertSame(
            (string) $testData->draftWikiIdentifier,
            (string) $wikiHistory->draftWikiIdentifier()
        );
        $this->assertSame(
            $testData->fromStatus,
            $wikiHistory->fromStatus()
        );
        $this->assertSame(
            $testData->toStatus,
            $wikiHistory->toStatus()
        );
        $this->assertSame(
            $testData->recordedAt,
            $wikiHistory->recordedAt()
        );
    }

    /**
     * 正常系: fromStatusがnullでも正しくインスタンスを作成できること
     */
    public function testConstructWithFromStatusNull(): void
    {
        $historyIdentifier = new WikiHistoryIdentifier(StrTestHelper::generateUuid());
        $actorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $toStatus = ApprovalStatus::Pending;
        $subjectName = new Name('TWICE');
        $recordedAt = new DateTimeImmutable();

        $wikiHistory = new WikiHistory(
            $historyIdentifier,
            HistoryActionType::DraftStatusChange,
            $actorIdentifier,
            null,
            $wikiIdentifier,
            null,
            null,
            $toStatus,
            null,
            null,
            $subjectName,
            $recordedAt,
        );

        $this->assertNull($wikiHistory->fromStatus());
        $this->assertSame($toStatus, $wikiHistory->toStatus());
    }

    /**
     * 正常系: Publishアクションでバージョン情報が正しく保持されること
     */
    public function testConstructWithPublishActionAndVersions(): void
    {
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $draftWikiIdentifier = new DraftWikiIdentifier(StrTestHelper::generateUuid());
        $fromVersion = new Version(1);
        $toVersion = new Version(2);

        $testData = $this->createDummyWikiHistory(
            wikiIdentifier: $wikiIdentifier,
            draftWikiIdentifier: $draftWikiIdentifier,
            actionType: HistoryActionType::Publish,
            fromVersion: $fromVersion,
            toVersion: $toVersion,
        );

        $wikiHistory = $testData->wikiHistory;

        $this->assertSame(HistoryActionType::Publish, $wikiHistory->actionType());
        $this->assertSame($fromVersion->value(), $wikiHistory->fromVersion()->value());
        $this->assertSame($toVersion->value(), $wikiHistory->toVersion()->value());
        $this->assertSame(
            (string) $wikiIdentifier,
            (string) $wikiHistory->wikiIdentifier()
        );
        $this->assertSame(
            (string) $draftWikiIdentifier,
            (string) $wikiHistory->draftWikiIdentifier()
        );
    }

    /**
     * 正常系: Rollbackアクションで正しくインスタンスを作成できること
     */
    public function testConstructWithRollbackAction(): void
    {
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $fromVersion = new Version(3);
        $toVersion = new Version(2);

        $testData = $this->createDummyWikiHistory(
            wikiIdentifier: $wikiIdentifier,
            actionType: HistoryActionType::Rollback,
            fromVersion: $fromVersion,
            toVersion: $toVersion,
        );

        $wikiHistory = $testData->wikiHistory;

        $this->assertSame(HistoryActionType::Rollback, $wikiHistory->actionType());
        $this->assertSame($fromVersion->value(), $wikiHistory->fromVersion()->value());
        $this->assertSame($toVersion->value(), $wikiHistory->toVersion()->value());
    }

    /**
     * 異常系: WikiとDraftのどちらもnullの場合は例外がスローされること
     */
    public function testWhenBothWikiAndDraftAreNull(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $historyIdentifier = new WikiHistoryIdentifier(StrTestHelper::generateUuid());
        $actorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $fromStatus = ApprovalStatus::Pending;
        $toStatus = ApprovalStatus::Rejected;
        $subjectName = new Name('TWICE');
        $recordedAt = new DateTimeImmutable();

        new WikiHistory(
            $historyIdentifier,
            HistoryActionType::DraftStatusChange,
            $actorIdentifier,
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
     * ダミーのWikiHistoryを作成するヘルパーメソッド
     */
    private function createDummyWikiHistory(
        ?WikiIdentifier $wikiIdentifier = null,
        ?DraftWikiIdentifier $draftWikiIdentifier = null,
        ?PrincipalIdentifier $submitterIdentifier = null,
        HistoryActionType $actionType = HistoryActionType::DraftStatusChange,
        ?Version $fromVersion = null,
        ?Version $toVersion = null,
    ): WikiHistoryTestData {
        $historyIdentifier = new WikiHistoryIdentifier(StrTestHelper::generateUuid());
        $actorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $fromStatus = ApprovalStatus::Pending;
        $toStatus = ApprovalStatus::Approved;
        $subjectName = new Name('TWICE');
        $recordedAt = new DateTimeImmutable();

        $wikiHistory = new WikiHistory(
            $historyIdentifier,
            $actionType,
            $actorIdentifier,
            $submitterIdentifier,
            $wikiIdentifier,
            $draftWikiIdentifier,
            $fromStatus,
            $toStatus,
            $fromVersion,
            $toVersion,
            $subjectName,
            $recordedAt,
        );

        return new WikiHistoryTestData(
            historyIdentifier: $historyIdentifier,
            actionType: $actionType,
            actorIdentifier: $actorIdentifier,
            submitterIdentifier: $submitterIdentifier,
            wikiIdentifier: $wikiIdentifier,
            draftWikiIdentifier: $draftWikiIdentifier,
            fromStatus: $fromStatus,
            toStatus: $toStatus,
            fromVersion: $fromVersion,
            toVersion: $toVersion,
            subjectName: $subjectName,
            recordedAt: $recordedAt,
            wikiHistory: $wikiHistory,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class WikiHistoryTestData
{
    public function __construct(
        public WikiHistoryIdentifier $historyIdentifier,
        public HistoryActionType     $actionType,
        public PrincipalIdentifier   $actorIdentifier,
        public ?PrincipalIdentifier  $submitterIdentifier,
        public ?WikiIdentifier       $wikiIdentifier,
        public ?DraftWikiIdentifier  $draftWikiIdentifier,
        public ApprovalStatus        $fromStatus,
        public ApprovalStatus        $toStatus,
        public ?Version              $fromVersion,
        public ?Version              $toVersion,
        public Name                  $subjectName,
        public DateTimeImmutable     $recordedAt,
        public WikiHistory           $wikiHistory,
    ) {
    }
}
