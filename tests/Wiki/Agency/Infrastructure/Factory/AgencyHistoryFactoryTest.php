<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Wiki\Agency\Domain\Factory\AgencyHistoryFactoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Infrastructure\Factory\AgencyHistoryFactory;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AgencyHistoryFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $agencyHistoryFactory = $this->app->make(AgencyHistoryFactoryInterface::class);
        $this->assertInstanceOf(AgencyHistoryFactory::class, $agencyHistoryFactory);
    }

    /**
     * 正常系: AgencyHistory Entityが正しく作成されること（agencyIdentifierのみ指定）.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithAgencyIdentifier(): void
    {
        $actionType = HistoryActionType::DraftStatusChange;
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $submitterIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $fromStatus = ApprovalStatus::Pending;
        $toStatus = ApprovalStatus::Approved;
        $agencyName = new AgencyName('JYP엔터테인먼트');

        $agencyHistoryFactory = $this->app->make(AgencyHistoryFactoryInterface::class);
        $agencyHistory = $agencyHistoryFactory->create(
            $actionType,
            $editorIdentifier,
            $submitterIdentifier,
            $agencyIdentifier,
            null,
            $fromStatus,
            $toStatus,
            null,
            null,
            $agencyName,
        );

        $this->assertTrue(UuidValidator::isValid((string)$agencyHistory->historyIdentifier()));
        $this->assertSame($actionType, $agencyHistory->actionType());
        $this->assertSame((string)$editorIdentifier, (string)$agencyHistory->editorIdentifier());
        $this->assertSame((string)$submitterIdentifier, (string)$agencyHistory->submitterIdentifier());
        $this->assertSame((string)$agencyIdentifier, (string)$agencyHistory->agencyIdentifier());
        $this->assertNull($agencyHistory->draftAgencyIdentifier());
        $this->assertSame($fromStatus, $agencyHistory->fromStatus());
        $this->assertSame($toStatus, $agencyHistory->toStatus());
        $this->assertNull($agencyHistory->fromVersion());
        $this->assertNull($agencyHistory->toVersion());
        $this->assertNotNull($agencyHistory->recordedAt());
        $this->assertSame($agencyName, $agencyHistory->subjectName());
    }

    /**
     * 正常系: AgencyHistory Entityが正しく作成されること（draftAgencyIdentifierのみ指定）.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithDraftAgencyIdentifier(): void
    {
        $actionType = HistoryActionType::DraftStatusChange;
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $draftAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $fromStatus = null;
        $toStatus = ApprovalStatus::Pending;
        $agencyName = new AgencyName('JYP엔터테인먼트');

        $agencyHistoryFactory = $this->app->make(AgencyHistoryFactoryInterface::class);
        $agencyHistory = $agencyHistoryFactory->create(
            $actionType,
            $editorIdentifier,
            null,
            null,
            $draftAgencyIdentifier,
            $fromStatus,
            $toStatus,
            null,
            null,
            $agencyName,
        );

        $this->assertTrue(UuidValidator::isValid((string)$agencyHistory->historyIdentifier()));
        $this->assertSame($actionType, $agencyHistory->actionType());
        $this->assertSame((string)$editorIdentifier, (string)$agencyHistory->editorIdentifier());
        $this->assertNull($agencyHistory->submitterIdentifier());
        $this->assertNull($agencyHistory->agencyIdentifier());
        $this->assertSame((string)$draftAgencyIdentifier, (string)$agencyHistory->draftAgencyIdentifier());
        $this->assertNull($agencyHistory->fromStatus());
        $this->assertSame($toStatus, $agencyHistory->toStatus());
        $this->assertNull($agencyHistory->fromVersion());
        $this->assertNull($agencyHistory->toVersion());
        $this->assertNotNull($agencyHistory->recordedAt());
    }

    /**
     * 正常系: AgencyHistory Entityが正しく作成されること（Rollback）.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithRollback(): void
    {
        $actionType = HistoryActionType::Rollback;
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $fromVersion = new Version(5);
        $toVersion = new Version(2);
        $agencyName = new AgencyName('JYP엔터테인먼트');

        $agencyHistoryFactory = $this->app->make(AgencyHistoryFactoryInterface::class);
        $agencyHistory = $agencyHistoryFactory->create(
            $actionType,
            $editorIdentifier,
            null,
            $agencyIdentifier,
            null,
            null,
            null,
            $fromVersion,
            $toVersion,
            $agencyName,
        );

        $this->assertTrue(UuidValidator::isValid((string)$agencyHistory->historyIdentifier()));
        $this->assertSame($actionType, $agencyHistory->actionType());
        $this->assertSame((string)$editorIdentifier, (string)$agencyHistory->editorIdentifier());
        $this->assertNull($agencyHistory->submitterIdentifier());
        $this->assertSame((string)$agencyIdentifier, (string)$agencyHistory->agencyIdentifier());
        $this->assertNull($agencyHistory->draftAgencyIdentifier());
        $this->assertNull($agencyHistory->fromStatus());
        $this->assertNull($agencyHistory->toStatus());
        $this->assertSame($fromVersion->value(), $agencyHistory->fromVersion()->value());
        $this->assertSame($toVersion->value(), $agencyHistory->toVersion()->value());
        $this->assertNotNull($agencyHistory->recordedAt());
    }
}
