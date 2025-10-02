<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\PublishSong;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Song\Application\Exception\ExistsApprovedButNotTranslatedSongException;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Application\UseCase\Command\PublishSong\PublishSong;
use Source\Wiki\Song\Application\UseCase\Command\PublishSong\PublishSongInput;
use Source\Wiki\Song\Application\UseCase\Command\PublishSong\PublishSongInterface;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\Factory\SongFactoryInterface;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\Service\SongServiceInterface;
use Source\Wiki\Song\Domain\ValueObject\BelongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PublishSongTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        // TODO: 各実装クラス作ったら削除する
        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $songService = Mockery::mock(SongServiceInterface::class);
        $this->app->instance(SongServiceInterface::class, $songService);
        $publishSong = $this->app->make(PublishSongInterface::class);
        $this->assertInstanceOf(PublishSong::class, $publishSong);
    }

    /**
     * 正常系：正しく変更されたSongが公開されること（すでに一度公開されたことがある場合）.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     */
    public function testProcessWhenAlreadyPublished(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
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
        $coverImagePath = new ImagePath('/resources/public/images/after.webp');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $input = new PublishSongInput(
            $songIdentifier,
            $publishedSongIdentifier,
        );

        $status = ApprovalStatus::UnderReview;
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $song = new DraftSong(
            $songIdentifier,
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
            $status,
        );

        $exName = new SongName('I CAN\'T STOP ME');
        $exBelongIdentifiers = [
            new BelongIdentifier(StrTestHelper::generateUlid()),
            new BelongIdentifier(StrTestHelper::generateUlid()),
        ];
        $exLyricist = new Lyricist('J.Y. Park');
        $exComposer = new Composer('Melanie Joy Fontana');
        $exReleaseDate = new ReleaseDate(new DateTimeImmutable('2020-10-26'));
        $exOverView = new Overview('\'I CAN\'T STOP ME\'는 80년대 신시사이저 사운드가 특징인 업템포의 레트로풍 댄스곡입니다. 가사는 선과 악의 갈림길에서 자기 자신을 제어하기 힘들어지는 갈등과, 멈출 수 없는 위험한 감정에 이끌리는 마음을 표현하고 있습니다. 파워풀한 퍼포먼스와 함께 트와이스의 새로운 매력을 보여준 곡으로 높은 평가를 받고 있습니다.');
        $exCoverImagePath = new ImagePath('/resources/public/images/before.webp');
        $exMusicVideoLink = new ExternalContentLink('https://example2.youtube.com/watch?v=dQw4w9WgXcQ');
        $publishedSong = new Song(
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $translation,
            $exName,
            $exBelongIdentifiers,
            $exLyricist,
            $exComposer,
            $exReleaseDate,
            $exOverView,
            $exCoverImagePath,
            $exMusicVideoLink,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($songIdentifier)
            ->andReturn($song);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with($publishedSongIdentifier)
            ->andReturn($publishedSong);
        $songRepository->shouldReceive('save')
            ->once()
            ->with($publishedSong)
            ->andReturn(null);
        $songRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($song)
            ->andReturn(null);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songService->shouldReceive('existsApprovedButNotTranslatedSong')
            ->once()
            ->with($translationSetIdentifier, $songIdentifier)
            ->andReturn(false);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);
        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishedSong = $publishSong->process($input);
        $this->assertSame((string)$publishedSongIdentifier, (string)$publishedSong->songIdentifier());
        $this->assertSame($translation->value, $publishedSong->translation()->value);
        $this->assertSame((string)$name, (string)$publishedSong->name());
        $this->assertSame($belongIdentifiers, $publishedSong->belongIdentifiers());
        $this->assertSame((string)$lyricist, (string)$publishedSong->lyricist());
        $this->assertSame((string)$composer, (string)$publishedSong->composer());
        $this->assertSame($releaseDate->value(), $publishedSong->releaseDate()->value());
        $this->assertSame((string)$overView, (string)$publishedSong->overView());
        $this->assertSame((string)$coverImagePath, (string)$publishedSong->coverImagePath());
        $this->assertSame((string)$musicVideoLink, (string)$publishedSong->musicVideoLink());
    }

    /**
     * 正常系：正しく変更されたSongが公開されること（初めて公開する場合）.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     */
    public function testProcessForTheFirstTime(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
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
        $coverImagePath = new ImagePath('/resources/public/images/after.webp');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $input = new PublishSongInput(
            $songIdentifier,
            $publishedSongIdentifier,
        );

        $status = ApprovalStatus::UnderReview;
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $song = new DraftSong(
            $songIdentifier,
            null,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
            $status,
        );

        $createdSong = new Song(
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $translation,
            $name,
            [],
            new Lyricist(''),
            new Composer(''),
            null,
            new Overview(''),
            null,
            null,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($songIdentifier)
            ->andReturn($song);
        $songRepository->shouldReceive('save')
            ->once()
            ->with($createdSong)
            ->andReturn(null);
        $songRepository->shouldReceive('deleteDraft')
            ->once()
            ->with($song)
            ->andReturn(null);

        $songFactory = Mockery::mock(SongFactoryInterface::class);
        $songFactory->shouldReceive('create')
            ->once()
            ->with($translationSetIdentifier, $translation, $name)
            ->andReturn($createdSong);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songService->shouldReceive('existsApprovedButNotTranslatedSong')
            ->once()
            ->with($translationSetIdentifier, $songIdentifier)
            ->andReturn(false);

        $this->app->instance(SongFactoryInterface::class, $songFactory);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);
        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishedSong = $publishSong->process($input);
        $this->assertSame((string)$publishedSongIdentifier, (string)$publishedSong->songIdentifier());
        $this->assertSame($translation->value, $publishedSong->translation()->value);
        $this->assertSame((string)$name, (string)$publishedSong->name());
        $this->assertSame($belongIdentifiers, $publishedSong->belongIdentifiers());
        $this->assertSame((string)$lyricist, (string)$publishedSong->lyricist());
        $this->assertSame((string)$composer, (string)$publishedSong->composer());
        $this->assertSame($releaseDate->value(), $publishedSong->releaseDate()->value());
        $this->assertSame((string)$overView, (string)$publishedSong->overView());
        $this->assertSame((string)$coverImagePath, (string)$publishedSong->coverImagePath());
        $this->assertSame((string)$musicVideoLink, (string)$publishedSong->musicVideoLink());
    }

    /**
     * 異常系：指定したIDに紐づくSongが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     */
    public function testWhenNotFoundSong(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $input = new PublishSongInput(
            $songIdentifier,
            $publishedSongIdentifier,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($songIdentifier)
            ->andReturn(null);

        $songService = Mockery::mock(SongServiceInterface::class);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);

        $this->expectException(SongNotFoundException::class);
        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishSong->process($input);
    }

    /**
     * 異常系：承認ステータスがUnderReview以外の場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     */
    public function testInvalidStatus(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
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
        $coverImagePath = new ImagePath('/resources/public/images/after.webp');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $input = new PublishSongInput(
            $songIdentifier,
            $publishedSongIdentifier,
        );

        $status = ApprovalStatus::Approved;
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $song = new DraftSong(
            $songIdentifier,
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
            $status,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($songIdentifier)
            ->andReturn($song);

        $songService = Mockery::mock(SongServiceInterface::class);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);

        $this->expectException(InvalidStatusException::class);
        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishSong->process($input);
    }

    /**
     * 異常系：承認済みだが、翻訳が反映されていない承認済みの歌がある場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     */
    public function testHasApprovedButNotTranslatedSong(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
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
        $coverImagePath = new ImagePath('/resources/public/images/after.webp');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $input = new PublishSongInput(
            $songIdentifier,
            $publishedSongIdentifier,
        );

        $status = ApprovalStatus::UnderReview;
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $song = new DraftSong(
            $songIdentifier,
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
            $status,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($songIdentifier)
            ->andReturn($song);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songService->shouldReceive('existsApprovedButNotTranslatedSong')
            ->once()
            ->with($translationSetIdentifier, $songIdentifier)
            ->andReturn(true);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);

        $this->expectException(ExistsApprovedButNotTranslatedSongException::class);
        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishSong->process($input);
    }

    /**
     * 異常系：公開されているメンバー情報が取得できない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     */
    public function testWhenNotFoundPublishedAgency(): void
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
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
        $coverImagePath = new ImagePath('/resources/public/images/after.webp');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $input = new PublishSongInput(
            $songIdentifier,
            $publishedSongIdentifier,
        );

        $status = ApprovalStatus::UnderReview;
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $song = new DraftSong(
            $songIdentifier,
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
            $status,
        );

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('findDraftById')
            ->once()
            ->with($songIdentifier)
            ->andReturn($song);
        $songRepository->shouldReceive('findById')
            ->once()
            ->with($publishedSongIdentifier)
            ->andReturn(null);

        $songService = Mockery::mock(SongServiceInterface::class);
        $songService->shouldReceive('existsApprovedButNotTranslatedSong')
            ->once()
            ->with($translationSetIdentifier, $songIdentifier)
            ->andReturn(false);

        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $this->app->instance(SongServiceInterface::class, $songService);

        $this->expectException(SongNotFoundException::class);
        $publishSong = $this->app->make(PublishSongInterface::class);
        $publishSong->process($input);
    }
}
