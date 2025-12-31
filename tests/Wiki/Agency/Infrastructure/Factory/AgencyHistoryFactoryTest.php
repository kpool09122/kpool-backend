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
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
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
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $submitterIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $fromStatus = ApprovalStatus::Pending;
        $toStatus = ApprovalStatus::Approved;
        $agencyName = new AgencyName('JYP엔터테인먼트');

        $agencyHistoryFactory = $this->app->make(AgencyHistoryFactoryInterface::class);
        $agencyHistory = $agencyHistoryFactory->create(
            $editorIdentifier,
            $submitterIdentifier,
            $agencyIdentifier,
            null,
            $fromStatus,
            $toStatus,
            $agencyName,
        );

        $this->assertTrue(UuidValidator::isValid((string)$agencyHistory->historyIdentifier()));
        $this->assertSame((string)$editorIdentifier, (string)$agencyHistory->editorIdentifier());
        $this->assertSame((string)$submitterIdentifier, (string)$agencyHistory->submitterIdentifier());
        $this->assertSame((string)$agencyIdentifier, (string)$agencyHistory->agencyIdentifier());
        $this->assertNull($agencyHistory->draftAgencyIdentifier());
        $this->assertSame($fromStatus, $agencyHistory->fromStatus());
        $this->assertSame($toStatus, $agencyHistory->toStatus());
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
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $draftAgencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $fromStatus = null;
        $toStatus = ApprovalStatus::Pending;
        $agencyName = new AgencyName('JYP엔터테인먼트');

        $agencyHistoryFactory = $this->app->make(AgencyHistoryFactoryInterface::class);
        $agencyHistory = $agencyHistoryFactory->create(
            $editorIdentifier,
            null,
            null,
            $draftAgencyIdentifier,
            $fromStatus,
            $toStatus,
            $agencyName,
        );

        $this->assertTrue(UuidValidator::isValid((string)$agencyHistory->historyIdentifier()));
        $this->assertSame((string)$editorIdentifier, (string)$agencyHistory->editorIdentifier());
        $this->assertNull($agencyHistory->submitterIdentifier());
        $this->assertNull($agencyHistory->agencyIdentifier());
        $this->assertSame((string)$draftAgencyIdentifier, (string)$agencyHistory->draftAgencyIdentifier());
        $this->assertNull($agencyHistory->fromStatus());
        $this->assertSame($toStatus, $agencyHistory->toStatus());
        $this->assertNotNull($agencyHistory->recordedAt());
    }
}
