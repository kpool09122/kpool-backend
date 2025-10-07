<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\TranslateSong;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Application\Service\TranslationServiceInterface;
use Source\Wiki\Song\Application\UseCase\Command\TranslateSong\TranslateSong;
use Source\Wiki\Song\Application\UseCase\Command\TranslateSong\TranslateSongInput;
use Source\Wiki\Song\Application\UseCase\Command\TranslateSong\TranslateSongInterface;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\ValueObject\BelongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TranslateSongTest extends TestCase
{
    /**
     * 正常系：DIが正しく動作すること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $songService = Mockery::mock(TranslationServiceInterface::class);
        $this->app->instance(TranslationServiceInterface::class, $songService);
        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $translateSong = $this->app->make(TranslateSongInterface::class);
        $this->assertInstanceOf(TranslateSong::class, $translateSong);
    }

    /**
     * 正常系：正しく他の言語に翻訳されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     */
    public function testProcess(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new SongName('TT');
        $belongIdentifiers = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다. 좋아한다는 마음을 전하고 싶은데 어떻게 해야 할지 몰라 눈물이 날 것 같기도 하고, 쿨한 척해 보기도 합니다. 그런 아직은 서투른 사랑의 마음을, 양손 엄지를 아래로 향하게 한 우는 이모티콘 "(T_T)"을 본뜬 "TT 포즈"로 재치있게 표현하고 있습니다. 핼러윈을 테마로 한 뮤직비디오도 특징이며, 멤버들이 다양한 캐릭터로 분장하여 애절하면서도 귀여운 세계관을 그려내고 있습니다.');
        $coverImagePath = new ImagePath('/resources/public/images/before.webp');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new TranslateSongInput(
            $songIdentifier,
            $principal,
        );

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $song = new Song(
            $songIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
        );

        $jaSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $japanese = Translation::JAPANESE;
        $jaName = new SongName('TT');
        $jaBelongIdentifiers = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];
        $jaLyricist = new Lyricist('Black Eyed Pilseung');
        $jaComposer = new Composer('Sam Lewis');
        $jaReleaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $jaOverView = new Overview('「TT」は初めて恋に落ちた少女の仕方がない心を歌った曲です。好きだという気持ちを伝えたいのですが、どうしたらいいのかわからず、涙が出るようで、クールなふりをしています。そんなまだ不器用な愛の心を、両手の親指を下に向けた泣く絵文字「(T_T)」を模した「TTポーズ」で気持ちよく表現しています。ハロウィンをテーマにしたミュージックビデオも特徴であり、メンバーたちが様々なキャラクターに扮し、切ないながらもかわいい世界観を描いています。');
        $jaCoverImagePath = new ImagePath('/resources/public/images/after1.webp');
        $jaMusicVideoLink = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');

        $jaSong = new DraftSong(
            $jaSongIdentifier,
            $songIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $japanese,
            $jaName,
            $jaBelongIdentifiers,
            $jaLyricist,
            $jaComposer,
            $jaReleaseDate,
            $jaOverView,
            $jaCoverImagePath,
            $jaMusicVideoLink,
            ApprovalStatus::Pending,
        );

        $enSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $english = Translation::ENGLISH;
        $enName = new SongName('TT');
        $enBelongIdentifiers = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];
        $enLyricist = new Lyricist('Black Eyed Pilseung');
        $enComposer = new Composer('Sam Lewis');
        $enReleaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $enOverView = new Overview('"TT" is a song about the helpless feelings of a girl who\'s fallen in love for the first time. She wants to express her feelings, but doesn\'t know how, so she feels close to tears or tries to act cool. This awkwardness in love is cleverly expressed with the "TT pose," modeled after the crying emoticon "(T_T)," with both thumbs pointing down. The Halloween-themed music video is also a standout, with the members dressed up as various characters, creating a world that\'s both poignant and cute.');
        $enCoverImagePath = new ImagePath('/resources/public/images/after2.webp');
        $enMusicVideoLink = new ExternalContentLink('https://example3.youtube.com/watch?v=dQw4w9WgXcQ');

        $enSong = new DraftSong(
            $enSongIdentifier,
            $songIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $english,
            $enName,
            $enBelongIdentifiers,
            $enLyricist,
            $enComposer,
            $enReleaseDate,
            $enOverView,
            $enCoverImagePath,
            $enMusicVideoLink,
            ApprovalStatus::Pending,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->with($songIdentifier)
            ->once()
            ->andReturn($song);
        $songRepository->shouldReceive('saveDraft')
            ->with($enSong)
            ->once()
            ->andReturn(null);
        $songRepository->shouldReceive('saveDraft')
            ->with($jaSong)
            ->once()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateSong')
            ->with($song, $english)
            ->once()
            ->andReturn($enSong);
        $translationService->shouldReceive('translateSong')
            ->with($song, $japanese)
            ->once()
            ->andReturn($jaSong);

        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $translateSong = $this->app->make(TranslateSongInterface::class);
        $songs = $translateSong->process($input);
        $this->assertCount(2, $songs);
        $this->assertSame($jaSong, $songs[0]);
        $this->assertSame($enSong, $songs[1]);
    }

    /**
     * 異常系： 指定したIDの歌情報が見つからない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testWhenSongNotFound(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new TranslateSongInput(
            $songIdentifier,
            $principal,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->with($songIdentifier)
            ->once()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $this->expectException(SongNotFoundException::class);
        $translateSong = $this->app->make(TranslateSongInterface::class);
        $translateSong->process($input);
    }

    /**
     * 異常系：翻訳権限がないロール（Collaborator）の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     */
    public function testUnauthorizedRole(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $belongIdentifiers = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::COLLABORATOR, null, [], null);

        $input = new TranslateSongInput(
            $songIdentifier,
            $principal,
        );

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $song = new Song(
            $songIdentifier,
            $translationSetIdentifier,
            Translation::KOREAN,
            new SongName('TT'),
            $belongIdentifiers,
            new Lyricist('블랙아이드필승'),
            new Composer('Sam Lewis'),
            new ReleaseDate(new DateTimeImmutable('2016-10-24')),
            new Overview('Overview'),
            new ImagePath('/resources/public/images/before.webp'),
            new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ'),
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with($songIdentifier)
            ->andReturn($song);

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $this->expectException(UnauthorizedException::class);
        $translateSong = $this->app->make(TranslateSongInterface::class);
        $translateSong->process($input);
    }

    /**
     * 正常系：ADMINISTRATORが楽曲を翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     */
    public function testProcessWithAdministrator(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $translation = Translation::KOREAN;
        $name = new SongName('TT');
        $belongIdentifiers = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('Overview');
        $coverImagePath = new ImagePath('/resources/public/images/before.webp');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::ADMINISTRATOR, null, [], null);

        $input = new TranslateSongInput(
            $songIdentifier,
            $principal,
        );

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $song = new Song(
            $songIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
        );

        $jaSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $japanese = Translation::JAPANESE;
        $jaSong = new DraftSong(
            $jaSongIdentifier,
            $songIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $japanese,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
            ApprovalStatus::Pending,
        );

        $enSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $english = Translation::ENGLISH;
        $enSong = new DraftSong(
            $enSongIdentifier,
            $songIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $english,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
            ApprovalStatus::Pending,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->with($songIdentifier)
            ->once()
            ->andReturn($song);
        $songRepository->shouldReceive('saveDraft')
            ->with($enSong)
            ->once()
            ->andReturn(null);
        $songRepository->shouldReceive('saveDraft')
            ->with($jaSong)
            ->once()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateSong')
            ->with($song, $english)
            ->once()
            ->andReturn($enSong);
        $translationService->shouldReceive('translateSong')
            ->with($song, $japanese)
            ->once()
            ->andReturn($jaSong);

        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $translateSong = $this->app->make(TranslateSongInterface::class);
        $songs = $translateSong->process($input);
        $this->assertCount(2, $songs);
    }

    /**
     * 異常系：GROUP_ACTORが自分の所属していないグループの楽曲を翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     */
    public function testUnauthorizedGroupScope(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $belongGroupId = StrTestHelper::generateUlid();
        $belongIdentifiers = [
            new BelongIdentifier($belongGroupId),
        ];

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $anotherGroupId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, null, [$anotherGroupId], null);

        $input = new TranslateSongInput(
            $songIdentifier,
            $principal,
        );

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $song = new Song(
            $songIdentifier,
            $translationSetIdentifier,
            Translation::KOREAN,
            new SongName('TT'),
            $belongIdentifiers,
            new Lyricist('블랙아이드필승'),
            new Composer('Sam Lewis'),
            new ReleaseDate(new DateTimeImmutable('2016-10-24')),
            new Overview('Overview'),
            new ImagePath('/resources/public/images/before.webp'),
            new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ'),
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with($songIdentifier)
            ->andReturn($song);

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $this->expectException(UnauthorizedException::class);
        $translateSong = $this->app->make(TranslateSongInterface::class);
        $translateSong->process($input);
    }

    /**
     * 正常系：GROUP_ACTORが自分の所属するグループの楽曲を翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     */
    public function testAuthorizedGroupActor(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $belongGroupId = StrTestHelper::generateUlid();
        $belongIdentifiers = [
            new BelongIdentifier($belongGroupId),
        ];

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $principal = new Principal($principalIdentifier, Role::GROUP_ACTOR, null, [$belongGroupId], null);

        $input = new TranslateSongInput(
            $songIdentifier,
            $principal,
        );

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $song = new Song(
            $songIdentifier,
            $translationSetIdentifier,
            Translation::KOREAN,
            new SongName('TT'),
            $belongIdentifiers,
            new Lyricist('블랙아이드필승'),
            new Composer('Sam Lewis'),
            new ReleaseDate(new DateTimeImmutable('2016-10-24')),
            new Overview('Overview'),
            new ImagePath('/resources/public/images/before.webp'),
            new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ'),
        );

        $jaSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $japanese = Translation::JAPANESE;
        $jaSong = new DraftSong(
            $jaSongIdentifier,
            $songIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $japanese,
            new SongName('TT'),
            $belongIdentifiers,
            new Lyricist('블랙아이드필승'),
            new Composer('Sam Lewis'),
            new ReleaseDate(new DateTimeImmutable('2016-10-24')),
            new Overview('Overview'),
            new ImagePath('/resources/public/images/before.webp'),
            new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ'),
            ApprovalStatus::Pending,
        );

        $enSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $english = Translation::ENGLISH;
        $enSong = new DraftSong(
            $enSongIdentifier,
            $songIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $english,
            new SongName('TT'),
            $belongIdentifiers,
            new Lyricist('블랙아이드필승'),
            new Composer('Sam Lewis'),
            new ReleaseDate(new DateTimeImmutable('2016-10-24')),
            new Overview('Overview'),
            new ImagePath('/resources/public/images/before.webp'),
            new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ'),
            ApprovalStatus::Pending,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->with($songIdentifier)
            ->once()
            ->andReturn($song);
        $songRepository->shouldReceive('saveDraft')
            ->with($enSong)
            ->once()
            ->andReturn(null);
        $songRepository->shouldReceive('saveDraft')
            ->with($jaSong)
            ->once()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateSong')
            ->with($song, $english)
            ->once()
            ->andReturn($enSong);
        $translationService->shouldReceive('translateSong')
            ->with($song, $japanese)
            ->once()
            ->andReturn($jaSong);

        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $translateSong = $this->app->make(TranslateSongInterface::class);
        $songs = $translateSong->process($input);
        $this->assertCount(2, $songs);
    }

    /**
     * 異常系：TALENT_ACTORが自分の所属していないグループの楽曲を翻訳しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     */
    public function testUnauthorizedTalentScope(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $belongGroupId = StrTestHelper::generateUlid();
        $belongIdentifiers = [
            new BelongIdentifier($belongGroupId),
        ];

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $anotherGroupId = StrTestHelper::generateUlid();
        $talentId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::TALENT_ACTOR, null, [$anotherGroupId], $talentId);

        $input = new TranslateSongInput(
            $songIdentifier,
            $principal,
        );

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $song = new Song(
            $songIdentifier,
            $translationSetIdentifier,
            Translation::KOREAN,
            new SongName('TT'),
            $belongIdentifiers,
            new Lyricist('블랙아이드필승'),
            new Composer('Sam Lewis'),
            new ReleaseDate(new DateTimeImmutable('2016-10-24')),
            new Overview('Overview'),
            new ImagePath('/resources/public/images/before.webp'),
            new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ'),
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with($songIdentifier)
            ->andReturn($song);

        $translationService = Mockery::mock(TranslationServiceInterface::class);

        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);

        $this->expectException(UnauthorizedException::class);
        $translateSong = $this->app->make(TranslateSongInterface::class);
        $translateSong->process($input);
    }

    /**
     * 正常系：TALENT_ACTORが自分の所属するグループの楽曲を翻訳できること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     */
    public function testAuthorizedTalentActor(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $editorIdentifier = new EditorIdentifier(StrTestHelper::generateUlid());
        $belongGroupId = StrTestHelper::generateUlid();
        $belongIdentifiers = [
            new BelongIdentifier($belongGroupId),
        ];

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $talentId = StrTestHelper::generateUlid();
        $principal = new Principal($principalIdentifier, Role::TALENT_ACTOR, null, [$belongGroupId], $talentId);

        $input = new TranslateSongInput(
            $songIdentifier,
            $principal,
        );

        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $song = new Song(
            $songIdentifier,
            $translationSetIdentifier,
            Translation::KOREAN,
            new SongName('TT'),
            $belongIdentifiers,
            new Lyricist('블랙아이드필승'),
            new Composer('Sam Lewis'),
            new ReleaseDate(new DateTimeImmutable('2016-10-24')),
            new Overview('Overview'),
            new ImagePath('/resources/public/images/before.webp'),
            new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ'),
        );

        $jaSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $japanese = Translation::JAPANESE;
        $jaSong = new DraftSong(
            $jaSongIdentifier,
            $songIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $japanese,
            new SongName('TT'),
            $belongIdentifiers,
            new Lyricist('블랙아이드필승'),
            new Composer('Sam Lewis'),
            new ReleaseDate(new DateTimeImmutable('2016-10-24')),
            new Overview('Overview'),
            new ImagePath('/resources/public/images/before.webp'),
            new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ'),
            ApprovalStatus::Pending,
        );

        $enSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $english = Translation::ENGLISH;
        $enSong = new DraftSong(
            $enSongIdentifier,
            $songIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $english,
            new SongName('TT'),
            $belongIdentifiers,
            new Lyricist('블랙아이드필승'),
            new Composer('Sam Lewis'),
            new ReleaseDate(new DateTimeImmutable('2016-10-24')),
            new Overview('Overview'),
            new ImagePath('/resources/public/images/before.webp'),
            new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ'),
            ApprovalStatus::Pending,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findById')
            ->with($songIdentifier)
            ->once()
            ->andReturn($song);
        $songRepository->shouldReceive('saveDraft')
            ->with($enSong)
            ->once()
            ->andReturn(null);
        $songRepository->shouldReceive('saveDraft')
            ->with($jaSong)
            ->once()
            ->andReturn(null);

        $translationService = Mockery::mock(TranslationServiceInterface::class);
        $translationService->shouldReceive('translateSong')
            ->with($song, $english)
            ->once()
            ->andReturn($enSong);
        $translationService->shouldReceive('translateSong')
            ->with($song, $japanese)
            ->once()
            ->andReturn($jaSong);

        $this->app->instance(TranslationServiceInterface::class, $translationService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $translateSong = $this->app->make(TranslateSongInterface::class);
        $songs = $translateSong->process($input);
        $this->assertCount(2, $songs);
    }
}
