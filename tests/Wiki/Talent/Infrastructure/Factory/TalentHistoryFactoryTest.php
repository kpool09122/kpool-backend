<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Domain\Factory\TalentHistoryFactoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Source\Wiki\Talent\Infrastructure\Factory\TalentHistoryFactory;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TalentHistoryFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $talentHistoryFactory = $this->app->make(TalentHistoryFactoryInterface::class);
        $this->assertInstanceOf(TalentHistoryFactory::class, $talentHistoryFactory);
    }

    /**
     * 正常系: TalentHistory Entityが正しく作成されること（talentIdentifierのみ指定）.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithTalentIdentifier(): void
    {
        $actionType = HistoryActionType::DraftStatusChange;
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $submitterIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $fromStatus = ApprovalStatus::Pending;
        $toStatus = ApprovalStatus::Approved;
        $subjectName = new TalentName('채영');

        $talentHistoryFactory = $this->app->make(TalentHistoryFactoryInterface::class);
        $talentHistory = $talentHistoryFactory->create(
            $actionType,
            $editorIdentifier,
            $submitterIdentifier,
            $talentIdentifier,
            null,
            $fromStatus,
            $toStatus,
            null,
            null,
            $subjectName,
        );

        $this->assertTrue(UuidValidator::isValid((string)$talentHistory->historyIdentifier()));
        $this->assertSame($actionType, $talentHistory->actionType());
        $this->assertSame((string)$editorIdentifier, (string)$talentHistory->editorIdentifier());
        $this->assertSame((string)$submitterIdentifier, (string)$talentHistory->submitterIdentifier());
        $this->assertSame((string)$talentIdentifier, (string)$talentHistory->talentIdentifier());
        $this->assertNull($talentHistory->draftTalentIdentifier());
        $this->assertSame($fromStatus, $talentHistory->fromStatus());
        $this->assertSame($toStatus, $talentHistory->toStatus());
        $this->assertNull($talentHistory->fromVersion());
        $this->assertNull($talentHistory->toVersion());
        $this->assertSame((string)$subjectName, (string)$talentHistory->subjectName());
        $this->assertNotNull($talentHistory->recordedAt());
    }

    /**
     * 正常系: TalentHistory Entityが正しく作成されること（draftTalentIdentifierのみ指定）.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithDraftTalentIdentifier(): void
    {
        $actionType = HistoryActionType::DraftStatusChange;
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $draftTalentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $fromStatus = null;
        $toStatus = ApprovalStatus::Pending;
        $subjectName = new TalentName('채영');

        $talentHistoryFactory = $this->app->make(TalentHistoryFactoryInterface::class);
        $talentHistory = $talentHistoryFactory->create(
            $actionType,
            $editorIdentifier,
            null,
            null,
            $draftTalentIdentifier,
            $fromStatus,
            $toStatus,
            null,
            null,
            $subjectName,
        );

        $this->assertTrue(UuidValidator::isValid((string)$talentHistory->historyIdentifier()));
        $this->assertSame($actionType, $talentHistory->actionType());
        $this->assertSame((string)$editorIdentifier, (string)$talentHistory->editorIdentifier());
        $this->assertNull($talentHistory->submitterIdentifier());
        $this->assertNull($talentHistory->talentIdentifier());
        $this->assertSame((string)$draftTalentIdentifier, (string)$talentHistory->draftTalentIdentifier());
        $this->assertNull($talentHistory->fromStatus());
        $this->assertSame($toStatus, $talentHistory->toStatus());
        $this->assertNull($talentHistory->fromVersion());
        $this->assertNull($talentHistory->toVersion());
        $this->assertSame((string)$subjectName, (string)$talentHistory->subjectName());
        $this->assertNotNull($talentHistory->recordedAt());
    }

    /**
     * 正常系: ロールバック用TalentHistory Entityが正しく作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithRollbackActionType(): void
    {
        $actionType = HistoryActionType::Rollback;
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $fromVersion = new Version(5);
        $toVersion = new Version(2);
        $subjectName = new TalentName('채영');

        $talentHistoryFactory = $this->app->make(TalentHistoryFactoryInterface::class);
        $talentHistory = $talentHistoryFactory->create(
            $actionType,
            $editorIdentifier,
            null,
            $talentIdentifier,
            null,
            null,
            null,
            $fromVersion,
            $toVersion,
            $subjectName,
        );

        $this->assertTrue(UuidValidator::isValid((string)$talentHistory->historyIdentifier()));
        $this->assertSame($actionType, $talentHistory->actionType());
        $this->assertSame((string)$editorIdentifier, (string)$talentHistory->editorIdentifier());
        $this->assertNull($talentHistory->submitterIdentifier());
        $this->assertSame((string)$talentIdentifier, (string)$talentHistory->talentIdentifier());
        $this->assertNull($talentHistory->draftTalentIdentifier());
        $this->assertNull($talentHistory->fromStatus());
        $this->assertNull($talentHistory->toStatus());
        $this->assertSame($fromVersion->value(), $talentHistory->fromVersion()->value());
        $this->assertSame($toVersion->value(), $talentHistory->toVersion()->value());
        $this->assertSame((string)$subjectName, (string)$talentHistory->subjectName());
        $this->assertNotNull($talentHistory->recordedAt());
    }
}
