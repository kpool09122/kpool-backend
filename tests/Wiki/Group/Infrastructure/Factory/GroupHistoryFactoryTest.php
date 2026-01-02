<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Wiki\Group\Domain\Factory\GroupHistoryFactoryInterface;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Infrastructure\Factory\GroupHistoryFactory;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GroupHistoryFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $groupHistoryFactory = $this->app->make(GroupHistoryFactoryInterface::class);
        $this->assertInstanceOf(GroupHistoryFactory::class, $groupHistoryFactory);
    }

    /**
     * 正常系: GroupHistory Entityが正しく作成されること（groupIdentifierのみ指定）.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithGroupIdentifier(): void
    {
        $actionType = HistoryActionType::DraftStatusChange;
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $submitterIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $fromStatus = ApprovalStatus::Pending;
        $toStatus = ApprovalStatus::Approved;
        $subjectName = new GroupName('TWICE');

        $groupHistoryFactory = $this->app->make(GroupHistoryFactoryInterface::class);
        $groupHistory = $groupHistoryFactory->create(
            $actionType,
            $editorIdentifier,
            $submitterIdentifier,
            $groupIdentifier,
            null,
            $fromStatus,
            $toStatus,
            null,
            null,
            $subjectName,
        );

        $this->assertTrue(UuidValidator::isValid((string)$groupHistory->historyIdentifier()));
        $this->assertSame($actionType, $groupHistory->actionType());
        $this->assertSame((string)$editorIdentifier, (string)$groupHistory->editorIdentifier());
        $this->assertSame((string)$submitterIdentifier, (string)$groupHistory->submitterIdentifier());
        $this->assertSame((string)$groupIdentifier, (string)$groupHistory->groupIdentifier());
        $this->assertNull($groupHistory->draftGroupIdentifier());
        $this->assertSame($fromStatus, $groupHistory->fromStatus());
        $this->assertSame($toStatus, $groupHistory->toStatus());
        $this->assertNull($groupHistory->fromVersion());
        $this->assertNull($groupHistory->toVersion());
        $this->assertSame((string)$subjectName, (string)$groupHistory->subjectName());
        $this->assertNotNull($groupHistory->recordedAt());
    }

    /**
     * 正常系: GroupHistory Entityが正しく作成されること（draftGroupIdentifierのみ指定）.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithDraftGroupIdentifier(): void
    {
        $actionType = HistoryActionType::DraftStatusChange;
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $draftGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $fromStatus = null;
        $toStatus = ApprovalStatus::Pending;
        $subjectName = new GroupName('TWICE');

        $groupHistoryFactory = $this->app->make(GroupHistoryFactoryInterface::class);
        $groupHistory = $groupHistoryFactory->create(
            $actionType,
            $editorIdentifier,
            null,
            null,
            $draftGroupIdentifier,
            $fromStatus,
            $toStatus,
            null,
            null,
            $subjectName,
        );

        $this->assertTrue(UuidValidator::isValid((string)$groupHistory->historyIdentifier()));
        $this->assertSame($actionType, $groupHistory->actionType());
        $this->assertSame((string)$editorIdentifier, (string)$groupHistory->editorIdentifier());
        $this->assertNull($groupHistory->submitterIdentifier());
        $this->assertNull($groupHistory->groupIdentifier());
        $this->assertSame((string)$draftGroupIdentifier, (string)$groupHistory->draftGroupIdentifier());
        $this->assertNull($groupHistory->fromStatus());
        $this->assertSame($toStatus, $groupHistory->toStatus());
        $this->assertNull($groupHistory->fromVersion());
        $this->assertNull($groupHistory->toVersion());
        $this->assertSame((string)$subjectName, (string)$groupHistory->subjectName());
        $this->assertNotNull($groupHistory->recordedAt());
    }

    /**
     * 正常系: Rollbackアクションの履歴が正しく作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithRollbackAction(): void
    {
        $actionType = HistoryActionType::Rollback;
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $fromVersion = new Version(3);
        $toVersion = new Version(1);
        $subjectName = new GroupName('TWICE');

        $groupHistoryFactory = $this->app->make(GroupHistoryFactoryInterface::class);
        $groupHistory = $groupHistoryFactory->create(
            $actionType,
            $editorIdentifier,
            null,
            $groupIdentifier,
            null,
            null,
            null,
            $fromVersion,
            $toVersion,
            $subjectName,
        );

        $this->assertTrue(UuidValidator::isValid((string)$groupHistory->historyIdentifier()));
        $this->assertSame($actionType, $groupHistory->actionType());
        $this->assertSame((string)$editorIdentifier, (string)$groupHistory->editorIdentifier());
        $this->assertSame((string)$groupIdentifier, (string)$groupHistory->groupIdentifier());
        $this->assertNull($groupHistory->fromStatus());
        $this->assertNull($groupHistory->toStatus());
        $this->assertSame($fromVersion->value(), $groupHistory->fromVersion()->value());
        $this->assertSame($toVersion->value(), $groupHistory->toVersion()->value());
        $this->assertSame((string)$subjectName, (string)$groupHistory->subjectName());
        $this->assertNotNull($groupHistory->recordedAt());
    }
}
