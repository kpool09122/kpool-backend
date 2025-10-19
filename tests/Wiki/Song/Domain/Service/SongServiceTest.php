<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Domain\Service;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\Service\SongService;
use Source\Wiki\Song\Domain\Service\SongServiceInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\BelongIdentifier;
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
        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $songService = $this->app->make(SongServiceInterface::class);
        $this->assertInstanceOf(SongService::class, $songService);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testExistsApprovedButNotTranslatedSongWhenApprovedExists(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $excludeSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());

        // Approved状態のDraftSongを作成（テストデータ）
        $approvedSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $approvedSong = new DraftSong(
            $approvedSongIdentifier,
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            Translation::KOREAN,
            new SongName('TT'),
            new AgencyIdentifier(StrTestHelper::generateUlid()),
            [
                new BelongIdentifier(StrTestHelper::generateUlid()),
                new BelongIdentifier(StrTestHelper::generateUlid()),
            ],
            new Lyricist('블랙아이드필승'),
            new Composer('Sam Lewis'),
            new ReleaseDate(new DateTimeImmutable('2016-10-24')),
            new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다. 좋아한다는 마음을 전하고 싶은데 어떻게 해야 할지 몰라 눈물이 날 것 같기도 하고, 쿨한 척해 보기도 합니다. 그런 아직은 서투른 사랑의 마음을, 양손 엄지를 아래로 향하게 한 우는 이모티콘 "(T_T)"을 본뜬 "TT 포즈"로 재치있게 표현하고 있습니다. 핼러윈을 테마로 한 뮤직비디오도 특징이며, 멤버들이 다양한 캐릭터로 분장하여 애절하면서도 귀여운 세계관을 그려내고 있습니다.'),
            new ImagePath('/resources/public/images/test.webp'),
            new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ'),
            ApprovalStatus::Approved,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftsByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$approvedSong]);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
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
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $excludeSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());

        // Pending状態のDraftSongを作成
        $pendingSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $pendingSong = new DraftSong(
            $pendingSongIdentifier,
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            Translation::JAPANESE,
            new SongName('TT'),
            new AgencyIdentifier(StrTestHelper::generateUlid()),
            [
                new BelongIdentifier(StrTestHelper::generateUlid()),
            ],
            new Lyricist('Black Eyed Pilseung'),
            new Composer('Sam Lewis'),
            new ReleaseDate(new DateTimeImmutable('2016-10-24')),
            new Overview('「TT」は初めて恋に落ちた少女の仕方がない心を歌った曲です。好きだという気持ちを伝えたいのですが、どうしたらいいのかわからず、涙が出るようで、クールなふりをしています。そんなまだ不器用な愛の心を、両手の親指を下に向けた泣く絵文字「(T_T)」を模した「TTポーズ」で気持ちよく表現しています。ハロウィンをテーマにしたミュージックビデオも特徴であり、メンバーたちが様々なキャラクターに扮し、切ないながらもかわいい世界観を描いています。'),
            new ImagePath('/resources/public/images/test-ja.webp'),
            new ExternalContentLink('https://example.youtube.com/watch?v=abcdef12345'),
            ApprovalStatus::Pending,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftsByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$pendingSong]);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
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
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());

        // 自分自身がApproved
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $selfSong = new DraftSong(
            $songIdentifier,
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            Translation::ENGLISH,
            new SongName('TT'),
            new AgencyIdentifier(StrTestHelper::generateUlid()),
            [
                new BelongIdentifier(StrTestHelper::generateUlid()),
                new BelongIdentifier(StrTestHelper::generateUlid()),
            ],
            new Lyricist('Black Eyed Pilseung'),
            new Composer('Sam Lewis'),
            new ReleaseDate(new DateTimeImmutable('2016-10-24')),
            new Overview('"TT" is a song about the helpless feelings of a girl who\'s fallen in love for the first time. She wants to express her feelings, but doesn\'t know how, so she feels close to tears or tries to act cool. This awkwardness in love is cleverly expressed with the "TT pose," modeled after the crying emoticon "(T_T)," with both thumbs pointing down. The Halloween-themed music video is also a standout, with the members dressed up as various characters, creating a world that\'s both poignant and cute.'),
            new ImagePath('/resources/public/images/test-en.webp'),
            new ExternalContentLink('https://example.youtube.com/watch?v=xyz789qwerty'),
            ApprovalStatus::Approved,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftsByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$selfSong]);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
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
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $excludeSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftsByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([]);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
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
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $excludeSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());

        // 複数のApproved状態のDraftSongを作成
        $approvedSongIdentifier1 = new SongIdentifier(StrTestHelper::generateUlid());
        $publishedSongIdentifier1 = new SongIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier1 = new EditorIdentifier(StrTestHelper::generateUlid());
        $approvedSong1 = new DraftSong(
            $approvedSongIdentifier1,
            $publishedSongIdentifier1,
            $translationSetIdentifier,
            $editorIdentifier1,
            Translation::KOREAN,
            new SongName('TT'),
            new AgencyIdentifier(StrTestHelper::generateUlid()),
            [
                new BelongIdentifier(StrTestHelper::generateUlid()),
                new BelongIdentifier(StrTestHelper::generateUlid()),
            ],
            new Lyricist('블랙아이드필승'),
            new Composer('Sam Lewis'),
            new ReleaseDate(new DateTimeImmutable('2016-10-24')),
            new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다. 좋아한다는 마음을 전하고 싶은데 어떻게 해야 할지 몰라 눈물이 날 것 같기도 하고, 쿨한 척해 보기도 합니다. 그런 아직은 서투른 사랑의 마음을, 양손 엄지를 아래로 향하게 한 우는 이모티콘 "(T_T)"을 본뜬 "TT 포즈"로 재치있게 표현하고 있습니다. 핼러윈을 테마로 한 뮤직비디오도 특징이며, 멤버들이 다양한 캐릭터로 분장하여 애절하면서도 귀여운 세계관을 그려내고 있습니다.'),
            new ImagePath('/resources/public/images/test-ko.webp'),
            new ExternalContentLink('https://example.youtube.com/watch?v=ePpPVE-GGJw'),
            ApprovalStatus::Approved,
        );

        $approvedSongIdentifier2 = new SongIdentifier(StrTestHelper::generateUlid());
        $publishedSongIdentifier2 = new SongIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier2 = new EditorIdentifier(StrTestHelper::generateUlid());
        $approvedSong2 = new DraftSong(
            $approvedSongIdentifier2,
            $publishedSongIdentifier2,
            $translationSetIdentifier,
            $editorIdentifier2,
            Translation::ENGLISH,
            new SongName('TT'),
            new AgencyIdentifier(StrTestHelper::generateUlid()),
            [
                new BelongIdentifier(StrTestHelper::generateUlid()),
                new BelongIdentifier(StrTestHelper::generateUlid()),
            ],
            new Lyricist('Black Eyed Pilseung'),
            new Composer('Sam Lewis'),
            new ReleaseDate(new DateTimeImmutable('2016-10-24')),
            new Overview('"TT" is a song about the helpless feelings of a girl who\'s fallen in love for the first time. She wants to express her feelings, but doesn\'t know how, so she feels close to tears or tries to act cool. This awkwardness in love is cleverly expressed with the "TT pose," modeled after the crying emoticon "(T_T)," with both thumbs pointing down. The Halloween-themed music video is also a standout, with the members dressed up as various characters, creating a world that\'s both poignant and cute.'),
            new ImagePath('/resources/public/images/test-en.webp'),
            new ExternalContentLink('https://example.youtube.com/watch?v=CM4CkVFmTds'),
            ApprovalStatus::Approved,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftsByTranslationSet')
            ->once()
            ->with($translationSetIdentifier)
            ->andReturn([$approvedSong1, $approvedSong2]);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $songService = $this->app->make(SongServiceInterface::class);

        $result = $songService->existsApprovedButNotTranslatedSong(
            $translationSetIdentifier,
            $excludeSongIdentifier,
        );

        $this->assertTrue($result);
    }
}
