<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Domain\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Ulid\UlidValidator;
use Source\Wiki\Agency\Domain\Factory\AgencyHistoryFactory;
use Source\Wiki\Agency\Domain\Factory\AgencyHistoryFactoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
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
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $submitterIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $fromStatus = ApprovalStatus::Pending;
        $toStatus = ApprovalStatus::Approved;

        $agencyHistoryFactory = $this->app->make(AgencyHistoryFactoryInterface::class);
        $agencyHistory = $agencyHistoryFactory->create(
            $editorIdentifier,
            $submitterIdentifier,
            $agencyIdentifier,
            null,
            $fromStatus,
            $toStatus,
        );

        $this->assertTrue(UlidValidator::isValid((string)$agencyHistory->historyIdentifier()));
        $this->assertSame((string)$editorIdentifier, (string)$agencyHistory->editorIdentifier());
        $this->assertSame((string)$submitterIdentifier, (string)$agencyHistory->submitterIdentifier());
        $this->assertSame((string)$agencyIdentifier, (string)$agencyHistory->agencyIdentifier());
        $this->assertNull($agencyHistory->draftAgencyIdentifier());
        $this->assertSame($fromStatus, $agencyHistory->fromStatus());
        $this->assertSame($toStatus, $agencyHistory->toStatus());
        $this->assertNotNull($agencyHistory->recordedAt());
    }

    /**
     * 正常系: AgencyHistory Entityが正しく作成されること（draftAgencyIdentifierのみ指定）.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithDraftAgencyIdentifier(): void
    {
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $draftAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $fromStatus = null;
        $toStatus = ApprovalStatus::Pending;

        $agencyHistoryFactory = $this->app->make(AgencyHistoryFactoryInterface::class);
        $agencyHistory = $agencyHistoryFactory->create(
            $editorIdentifier,
            null,
            null,
            $draftAgencyIdentifier,
            $fromStatus,
            $toStatus,
        );

        $this->assertTrue(UlidValidator::isValid((string)$agencyHistory->historyIdentifier()));
        $this->assertSame((string)$editorIdentifier, (string)$agencyHistory->editorIdentifier());
        $this->assertNull($agencyHistory->submitterIdentifier());
        $this->assertNull($agencyHistory->agencyIdentifier());
        $this->assertSame((string)$draftAgencyIdentifier, (string)$agencyHistory->draftAgencyIdentifier());
        $this->assertNull($agencyHistory->fromStatus());
        $this->assertSame($toStatus, $agencyHistory->toStatus());
        $this->assertNotNull($agencyHistory->recordedAt());
    }
}
