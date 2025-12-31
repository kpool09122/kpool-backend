<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Song\Domain\Factory\SongHistoryFactoryInterface;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Source\Wiki\Song\Infrastructure\Factory\SongHistoryFactory;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SongHistoryFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $songHistoryFactory = $this->app->make(SongHistoryFactoryInterface::class);
        $this->assertInstanceOf(SongHistoryFactory::class, $songHistoryFactory);
    }

    /**
     * 正常系: SongHistory Entityが正しく作成されること（songIdentifierのみ指定）.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithSongIdentifier(): void
    {
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUuid());
        $submitterIdentifier = new EditorIdentifier(StrTestHelper::generateUuid());
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $fromStatus = ApprovalStatus::Pending;
        $toStatus = ApprovalStatus::Approved;
        $subjectName = new SongName('Dynamite');

        $songHistoryFactory = $this->app->make(SongHistoryFactoryInterface::class);
        $songHistory = $songHistoryFactory->create(
            $editorIdentifier,
            $submitterIdentifier,
            $songIdentifier,
            null,
            $fromStatus,
            $toStatus,
            $subjectName,
        );

        $this->assertTrue(UuidValidator::isValid((string)$songHistory->historyIdentifier()));
        $this->assertSame((string)$editorIdentifier, (string)$songHistory->editorIdentifier());
        $this->assertSame((string)$submitterIdentifier, (string)$songHistory->submitterIdentifier());
        $this->assertSame((string)$songIdentifier, (string)$songHistory->songIdentifier());
        $this->assertNull($songHistory->draftSongIdentifier());
        $this->assertSame($fromStatus, $songHistory->fromStatus());
        $this->assertSame($toStatus, $songHistory->toStatus());
        $this->assertSame((string)$subjectName, (string)$songHistory->subjectName());
        $this->assertNotNull($songHistory->recordedAt());
    }

    /**
     * 正常系: SongHistory Entityが正しく作成されること（draftSongIdentifierのみ指定）.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithDraftSongIdentifier(): void
    {
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUuid());
        $draftSongIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $fromStatus = null;
        $toStatus = ApprovalStatus::Pending;
        $subjectName = new SongName('Dynamite');

        $songHistoryFactory = $this->app->make(SongHistoryFactoryInterface::class);
        $songHistory = $songHistoryFactory->create(
            $editorIdentifier,
            null,
            null,
            $draftSongIdentifier,
            $fromStatus,
            $toStatus,
            $subjectName,
        );

        $this->assertTrue(UuidValidator::isValid((string)$songHistory->historyIdentifier()));
        $this->assertSame((string)$editorIdentifier, (string)$songHistory->editorIdentifier());
        $this->assertNull($songHistory->submitterIdentifier());
        $this->assertNull($songHistory->songIdentifier());
        $this->assertSame((string)$draftSongIdentifier, (string)$songHistory->draftSongIdentifier());
        $this->assertNull($songHistory->fromStatus());
        $this->assertSame($toStatus, $songHistory->toStatus());
        $this->assertSame((string)$subjectName, (string)$songHistory->subjectName());
        $this->assertNotNull($songHistory->recordedAt());
    }
}
