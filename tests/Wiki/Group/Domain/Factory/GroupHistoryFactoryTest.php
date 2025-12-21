<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Domain\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Ulid\UlidValidator;
use Source\Wiki\Group\Domain\Factory\GroupHistoryFactory;
use Source\Wiki\Group\Domain\Factory\GroupHistoryFactoryInterface;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
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
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $submitterIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $fromStatus = ApprovalStatus::Pending;
        $toStatus = ApprovalStatus::Approved;
        $subjectName = new GroupName('TWICE');

        $groupHistoryFactory = $this->app->make(GroupHistoryFactoryInterface::class);
        $groupHistory = $groupHistoryFactory->create(
            $editorIdentifier,
            $submitterIdentifier,
            $groupIdentifier,
            null,
            $fromStatus,
            $toStatus,
            $subjectName,
        );

        $this->assertTrue(UlidValidator::isValid((string)$groupHistory->historyIdentifier()));
        $this->assertSame((string)$editorIdentifier, (string)$groupHistory->editorIdentifier());
        $this->assertSame((string)$submitterIdentifier, (string)$groupHistory->submitterIdentifier());
        $this->assertSame((string)$groupIdentifier, (string)$groupHistory->groupIdentifier());
        $this->assertNull($groupHistory->draftGroupIdentifier());
        $this->assertSame($fromStatus, $groupHistory->fromStatus());
        $this->assertSame($toStatus, $groupHistory->toStatus());
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
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $draftGroupIdentifier = new GroupIdentifier(StrTestHelper::generateUlid());
        $fromStatus = null;
        $toStatus = ApprovalStatus::Pending;
        $subjectName = new GroupName('TWICE');

        $groupHistoryFactory = $this->app->make(GroupHistoryFactoryInterface::class);
        $groupHistory = $groupHistoryFactory->create(
            $editorIdentifier,
            null,
            null,
            $draftGroupIdentifier,
            $fromStatus,
            $toStatus,
            $subjectName,
        );

        $this->assertTrue(UlidValidator::isValid((string)$groupHistory->historyIdentifier()));
        $this->assertSame((string)$editorIdentifier, (string)$groupHistory->editorIdentifier());
        $this->assertNull($groupHistory->submitterIdentifier());
        $this->assertNull($groupHistory->groupIdentifier());
        $this->assertSame((string)$draftGroupIdentifier, (string)$groupHistory->draftGroupIdentifier());
        $this->assertNull($groupHistory->fromStatus());
        $this->assertSame($toStatus, $groupHistory->toStatus());
        $this->assertSame((string)$subjectName, (string)$groupHistory->subjectName());
        $this->assertNotNull($groupHistory->recordedAt());
    }
}
