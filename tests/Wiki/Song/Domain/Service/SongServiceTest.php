<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Domain\Service;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Repository\DraftSongRepositoryInterface;
use Source\Wiki\Song\Domain\Service\SongService;
use Source\Wiki\Song\Domain\Service\SongServiceInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SongServiceTest extends TestCase
{
    /**
     * DIが正しく動作すること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $songRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $this->app->instance(DraftSongRepositoryInterface::class, $songRepository);
        $songService = $this->app->make(SongServiceInterface::class);
        $this->assertInstanceOf(SongService::class, $songService);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testExistsApprovedButNotTranslatedSongWhenApprovedExists(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $excludeSongIdentifier = new SongIdentifier(StrTestHelper::generateUuid());

        $approvedSong = $this->createDraftSong(
            translationSetIdentifier: $translationSetIdentifier,
            approvalStatus: ApprovalStatus::Approved,
        );

        $songRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $songRepository->shouldReceive('findByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$approvedSong]);

        $this->app->instance(DraftSongRepositoryInterface::class, $songRepository);
        $songService = $this->app->make(SongServiceInterface::class);

        $result = $songService->existsApprovedButNotTranslatedSong(
            $translationSetIdentifier,
            $excludeSongIdentifier,
        );

        $this->assertTrue($result);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testExistsApprovedButNotTranslatedSongWhenNoApproved(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $excludeSongIdentifier = new SongIdentifier(StrTestHelper::generateUuid());

        $pendingSong = $this->createDraftSong(
            translationSetIdentifier: $translationSetIdentifier,
            approvalStatus: ApprovalStatus::Pending,
        );

        $songRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $songRepository->shouldReceive('findByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$pendingSong]);

        $this->app->instance(DraftSongRepositoryInterface::class, $songRepository);
        $songService = $this->app->make(SongServiceInterface::class);

        $result = $songService->existsApprovedButNotTranslatedSong(
            $translationSetIdentifier,
            $excludeSongIdentifier,
        );

        $this->assertFalse($result);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testExistsApprovedButNotTranslatedSongWhenOnlySelfIsApproved(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());

        $selfSong = $this->createDraftSong(
            songIdentifier: $songIdentifier,
            translationSetIdentifier: $translationSetIdentifier,
            approvalStatus: ApprovalStatus::Approved,
        );

        $songRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $songRepository->shouldReceive('findByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$selfSong]);

        $this->app->instance(DraftSongRepositoryInterface::class, $songRepository);
        $songService = $this->app->make(SongServiceInterface::class);

        $result = $songService->existsApprovedButNotTranslatedSong(
            $translationSetIdentifier,
            $songIdentifier,  // 自分自身を除外
        );

        $this->assertFalse($result);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testExistsApprovedButNotTranslatedSongWhenNoDrafts(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $excludeSongIdentifier = new SongIdentifier(StrTestHelper::generateUuid());

        $songRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $songRepository->shouldReceive('findByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([]);

        $this->app->instance(DraftSongRepositoryInterface::class, $songRepository);
        $songService = $this->app->make(SongServiceInterface::class);

        $result = $songService->existsApprovedButNotTranslatedSong(
            $translationSetIdentifier,
            $excludeSongIdentifier,
        );

        $this->assertFalse($result);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testExistsApprovedButNotTranslatedSongWhenMultipleApprovedExists(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $excludeSongIdentifier = new SongIdentifier(StrTestHelper::generateUuid());

        $approvedSong1 = $this->createDraftSong(
            translationSetIdentifier: $translationSetIdentifier,
            approvalStatus: ApprovalStatus::Approved,
        );

        $approvedSong2 = $this->createDraftSong(
            translationSetIdentifier: $translationSetIdentifier,
            approvalStatus: ApprovalStatus::Approved,
        );

        $songRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $songRepository->shouldReceive('findByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$approvedSong1, $approvedSong2]);

        $this->app->instance(DraftSongRepositoryInterface::class, $songRepository);
        $songService = $this->app->make(SongServiceInterface::class);

        $result = $songService->existsApprovedButNotTranslatedSong(
            $translationSetIdentifier,
            $excludeSongIdentifier,
        );

        $this->assertTrue($result);
    }

    private function createDraftSong(
        ?SongIdentifier $songIdentifier = null,
        ?SongIdentifier $publishedSongIdentifier = null,
        ?TranslationSetIdentifier $translationSetIdentifier = null,
        ?Slug $slug = null,
        ?PrincipalIdentifier $editorIdentifier = null,
        Language $language = Language::JAPANESE,
        ?SongName $songName = null,
        ?AgencyIdentifier $agencyIdentifier = null,
        ?GroupIdentifier $groupIdentifier = null,
        ?TalentIdentifier $talentIdentifier = null,
        ?Lyricist $lyricist = null,
        ?Composer $composer = null,
        ?ReleaseDate $releaseDate = null,
        ?Overview $overview = null,
        ApprovalStatus $approvalStatus = ApprovalStatus::Pending,
    ): DraftSong {
        return new DraftSong(
            $songIdentifier ?? new SongIdentifier(StrTestHelper::generateUuid()),
            $publishedSongIdentifier,
            $translationSetIdentifier ?? new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            $slug ?? new Slug('test-song'),
            $editorIdentifier,
            $language,
            $songName ?? new SongName('Test Song'),
            $agencyIdentifier,
            $groupIdentifier,
            $talentIdentifier,
            $lyricist ?? new Lyricist('Test Lyricist'),
            $composer ?? new Composer('Test Composer'),
            $releaseDate,
            $overview ?? new Overview('Test overview'),
            $approvalStatus,
        );
    }
}
