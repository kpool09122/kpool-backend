<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Wiki\Agency\Domain\Entity\AgencyHistory;
use Source\Wiki\Agency\Domain\ValueObject\AgencyHistoryIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AgencyHistoryTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスを作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $submitterIdentifier = new EditorIdentifier(StrTestHelper::generateUuid());
        $createAgencyHistory = $this->createDummyAgencyHistory(
            agencyIdentifier: $agencyIdentifier,
            submitterIdentifier: $submitterIdentifier,
        );

        $agencyHistory = $createAgencyHistory->agencyHistory;

        $this->assertSame(
            (string)$createAgencyHistory->historyIdentifier,
            (string)$agencyHistory->historyIdentifier()
        );
        $this->assertSame(
            (string)$createAgencyHistory->editorIdentifier,
            (string)$agencyHistory->editorIdentifier()
        );
        $this->assertSame(
            (string)$createAgencyHistory->submitterIdentifier,
            (string)$agencyHistory->submitterIdentifier()
        );
        $this->assertSame(
            (string)$createAgencyHistory->agencyIdentifier,
            (string)$agencyHistory->agencyIdentifier()
        );
        $this->assertNull($createAgencyHistory->draftAgencyIdentifier);
        $this->assertSame(
            $createAgencyHistory->fromStatus,
            $agencyHistory->fromStatus()
        );
        $this->assertSame(
            $createAgencyHistory->toStatus,
            $agencyHistory->toStatus()
        );
        $this->assertSame(
            $createAgencyHistory->recordedAt,
            $agencyHistory->recordedAt()
        );
    }

    /**
     * 正常系: agencyIdentifierのみがnullでも正しくインスタンスを作成できること.
     *
     * @return void
     */
    public function testConstructWithOnlyAgencyIdentifierNull(): void
    {
        $draftAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $createAgencyHistory = $this->createDummyAgencyHistory(draftAgencyIdentifier: $draftAgencyIdentifier);

        $agencyHistory = $createAgencyHistory->agencyHistory;

        $this->assertSame(
            (string)$createAgencyHistory->historyIdentifier,
            (string)$agencyHistory->historyIdentifier()
        );
        $this->assertSame(
            (string)$createAgencyHistory->editorIdentifier,
            (string)$agencyHistory->editorIdentifier()
        );
        $this->assertNull($createAgencyHistory->agencyIdentifier);
        $this->assertSame(
            (string)$createAgencyHistory->draftAgencyIdentifier,
            (string)$agencyHistory->draftAgencyIdentifier()
        );
        $this->assertSame(
            $createAgencyHistory->fromStatus,
            $agencyHistory->fromStatus()
        );
        $this->assertSame(
            $createAgencyHistory->toStatus,
            $agencyHistory->toStatus()
        );
        $this->assertSame(
            $createAgencyHistory->recordedAt,
            $agencyHistory->recordedAt()
        );
    }

    /**
     * 正常系: fromStatusがnullでも正しくインスタンスを作成できること.
     *
     * @return void
     */
    public function testConstructWithFromStatusNull(): void
    {
        $historyIdentifier = new AgencyHistoryIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUuid());
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $toStatus = ApprovalStatus::Pending;
        $agencyName = new AgencyName('JYP엔터테인먼트');
        $recordedAt = new DateTimeImmutable();

        $agencyHistory = new AgencyHistory(
            $historyIdentifier,
            $editorIdentifier,
            null,
            $agencyIdentifier,
            null,
            null,
            $toStatus,
            $agencyName,
            $recordedAt,
        );

        $this->assertNull($agencyHistory->fromStatus());
        $this->assertSame($toStatus, $agencyHistory->toStatus());
    }

    /**
     * 異常系: AgencyとDraftのどちらもNullの場合は例外がスローされること.
     *
     * @return void
     */
    public function testWhenBothAgencyAndDraftAreNull(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $historyIdentifier = new AgencyHistoryIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUuid());
        $fromStatus = ApprovalStatus::Pending;
        $toStatus = ApprovalStatus::Rejected;
        $agencyName = new AgencyName('JYP엔터테인먼트');
        $recordedAt = new DateTimeImmutable();

        new AgencyHistory(
            $historyIdentifier,
            $editorIdentifier,
            null,
            null,
            null,
            $fromStatus,
            $toStatus,
            $agencyName,
            $recordedAt,
        );
    }

    /**
     * ダミーのAgencyHistoryを作成するヘルパーメソッド
     *
     * @param ?AgencyIdentifier $agencyIdentifier
     * @param ?AgencyIdentifier $draftAgencyIdentifier
     * @param ?EditorIdentifier $submitterIdentifier
     * @return AgencyHistoryTestData
     */
    private function createDummyAgencyHistory(
        ?AgencyIdentifier $agencyIdentifier = null,
        ?AgencyIdentifier $draftAgencyIdentifier = null,
        ?EditorIdentifier $submitterIdentifier = null,
    ): AgencyHistoryTestData {
        $historyIdentifier = new AgencyHistoryIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUuid());
        $fromStatus = ApprovalStatus::Pending;
        $toStatus = ApprovalStatus::Approved;
        $agencyName = new AgencyName('JYP엔터테인먼트');
        $recordedAt = new DateTimeImmutable();

        $agencyHistory = new AgencyHistory(
            $historyIdentifier,
            $editorIdentifier,
            $submitterIdentifier,
            $agencyIdentifier,
            $draftAgencyIdentifier,
            $fromStatus,
            $toStatus,
            $agencyName,
            $recordedAt,
        );

        return new AgencyHistoryTestData(
            historyIdentifier: $historyIdentifier,
            editorIdentifier: $editorIdentifier,
            submitterIdentifier: $submitterIdentifier,
            agencyIdentifier: $agencyIdentifier,
            draftAgencyIdentifier: $draftAgencyIdentifier,
            fromStatus: $fromStatus,
            toStatus: $toStatus,
            recordedAt: $recordedAt,
            agencyHistory: $agencyHistory,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class AgencyHistoryTestData
{
    public function __construct(
        public AgencyHistoryIdentifier $historyIdentifier,
        public EditorIdentifier        $editorIdentifier,
        public ?EditorIdentifier       $submitterIdentifier,
        public ?AgencyIdentifier       $agencyIdentifier,
        public ?AgencyIdentifier       $draftAgencyIdentifier,
        public ApprovalStatus          $fromStatus,
        public ApprovalStatus          $toStatus,
        public DateTimeImmutable       $recordedAt,
        public AgencyHistory           $agencyHistory,
    ) {
    }
}
