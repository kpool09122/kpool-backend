<?php

namespace Tests\Wiki\Song\UseCase\Command\CreateSong;

use Businesses\Shared\Service\ImageServiceInterface;
use Businesses\Shared\Service\Ulid\UlidValidator;
use Businesses\Shared\ValueObject\ExternalContentLink;
use Businesses\Shared\ValueObject\ImagePath;
use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Song\Domain\Entity\Song;
use Businesses\Wiki\Song\Domain\Factory\SongFactoryInterface;
use Businesses\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Businesses\Wiki\Song\Domain\ValueObject\BelongIdentifier;
use Businesses\Wiki\Song\Domain\ValueObject\Composer;
use Businesses\Wiki\Song\Domain\ValueObject\Lyricist;
use Businesses\Wiki\Song\Domain\ValueObject\Overview;
use Businesses\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Businesses\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Businesses\Wiki\Song\Domain\ValueObject\SongName;
use Businesses\Wiki\Song\UseCase\Command\CreateSong\CreateSong;
use Businesses\Wiki\Song\UseCase\Command\CreateSong\CreateSongInput;
use Businesses\Wiki\Song\UseCase\Command\CreateSong\CreateSongInterface;
use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreateSongTest extends TestCase
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
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $createSong = $this->app->make(CreateSongInterface::class);
        $this->assertInstanceOf(CreateSong::class, $createSong);
    }

    /**
     * 正常系：正しくSong Entityが作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
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
        $base64EncodedCoverImage = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $input = new CreateSongInput(
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $base64EncodedCoverImage,
            $musicVideoLink
        );

        $coverImagePath = new ImagePath('/resources/public/images/before.webp');
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($base64EncodedCoverImage)
            ->andReturn($coverImagePath);

        $songIdentifier = new SongIdentifier(StrTestHelper::generateUlid());
        $song = new Song(
            $songIdentifier,
            $translation,
            $name,
            $belongIdentifiers,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink
        );
        $songFactory = Mockery::mock(SongFactoryInterface::class);
        $songFactory->shouldReceive('create')
            ->once()
            ->with($translation, $name)
            ->andReturn($song);

        $songRepository = Mockery::mock(SongRepositoryInterface::class);
        $songRepository->shouldReceive('save')
            ->once()
            ->with($song)
            ->andReturn(null);

        $this->app->instance(ImageServiceInterface::class, $imageService);
        $this->app->instance(SongFactoryInterface::class, $songFactory);
        $this->app->instance(SongRepositoryInterface::class, $songRepository);
        $createSong = $this->app->make(CreateSongInterface::class);
        $song = $createSong->process($input);
        $this->assertTrue(UlidValidator::isValid((string)$song->songIdentifier()));
        $this->assertSame($translation->value, $song->translation()->value);
        $this->assertSame((string)$name, (string)$song->name());
        $this->assertSame($belongIdentifiers, $song->belongIdentifiers());
        $this->assertSame((string)$lyricist, (string)$song->lyricist());
        $this->assertSame((string)$composer, (string)$song->composer());
        $this->assertSame($releaseDate->value(), $input->releaseDate()->value());
        $this->assertSame((string)$overView, (string)$song->overView());
        $this->assertSame((string)$coverImagePath, (string)$song->coverImagePath());
        $this->assertSame((string)$musicVideoLink, (string)$song->musicVideoLink());
    }
}
