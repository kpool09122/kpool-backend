<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\UseCase\Command\MergeSong;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Application\UseCase\Command\MergeSong\MergeSong;
use Source\Wiki\Song\Application\UseCase\Command\MergeSong\MergeSongInput;
use Source\Wiki\Song\Application\UseCase\Command\MergeSong\MergeSongInterface;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Repository\DraftSongRepositoryInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class MergeSongTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);
        $mergeSong = $this->app->make(MergeSongInterface::class);
        $this->assertInstanceOf(MergeSong::class, $mergeSong);
    }

    /**
     * 正常系：正しくSong Entityがマージされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcess(): void
    {
        $dummySong = $this->createDummyMergeSong();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::ADMINISTRATOR, null, [], []);

        $input = new MergeSongInput(
            $dummySong->songIdentifier,
            $dummySong->name,
            $dummySong->agencyIdentifier,
            $dummySong->groupIdentifier,
            $dummySong->talentIdentifier,
            $dummySong->lyricist,
            $dummySong->composer,
            $dummySong->releaseDate,
            $dummySong->overView,
            $dummySong->musicVideoLink,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('save')
            ->once()
            ->with($dummySong->song)
            ->andReturn(null);
        $draftSongRepository->shouldReceive('findById')
            ->once()
            ->with($dummySong->songIdentifier)
            ->andReturn($dummySong->song);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);
        $mergeSong = $this->app->make(MergeSongInterface::class);
        $song = $mergeSong->process($input);
        $this->assertSame((string)$dummySong->songIdentifier, (string)$song->songIdentifier());
        $this->assertSame((string)$dummySong->publishedSongIdentifier, (string)$song->publishedSongIdentifier());
        $this->assertSame((string)$dummySong->editorIdentifier, (string)$song->editorIdentifier());
        $this->assertSame($dummySong->language->value, $song->language()->value);
        $this->assertSame((string)$dummySong->name, (string)$song->name());
        $this->assertSame((string)$dummySong->agencyIdentifier, (string)$song->agencyIdentifier());
        $this->assertSame((string)$dummySong->groupIdentifier, (string)$song->groupIdentifier());
        $this->assertSame((string)$dummySong->talentIdentifier, (string)$song->talentIdentifier());
        $this->assertSame((string)$dummySong->lyricist, (string)$song->lyricist());
        $this->assertSame((string)$dummySong->composer, (string)$song->composer());
        $this->assertSame($dummySong->releaseDate->value(), $song->releaseDate()->value());
        $this->assertSame((string)$dummySong->overView, (string)$song->overView());
        $this->assertSame((string)$dummySong->musicVideoLink, (string)$song->musicVideoLink());
        $this->assertSame($principalIdentifier, $song->mergerIdentifier());
        $this->assertSame($mergedAt, $song->mergedAt());
    }

    /**
     * 異常系：指定したIDに紐づくSongが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testWhenNotFoundSong(): void
    {
        $dummySong = $this->createDummyMergeSong();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new MergeSongInput(
            $dummySong->songIdentifier,
            $dummySong->name,
            $dummySong->agencyIdentifier,
            $dummySong->groupIdentifier,
            $dummySong->talentIdentifier,
            $dummySong->lyricist,
            $dummySong->composer,
            $dummySong->releaseDate,
            $dummySong->overView,
            $dummySong->musicVideoLink,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldNotReceive('findById');

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('findById')
            ->once()
            ->with($dummySong->songIdentifier)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);
        $this->expectException(SongNotFoundException::class);
        $mergeSong = $this->app->make(MergeSongInterface::class);
        $mergeSong->process($input);
    }

    /**
     * 異常系：指定したIDに紐づくPrincipalが存在しない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     */
    public function testWhenNotFoundPrincipal(): void
    {
        $dummySong = $this->createDummyMergeSong();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new MergeSongInput(
            $dummySong->songIdentifier,
            $dummySong->name,
            $dummySong->agencyIdentifier,
            $dummySong->groupIdentifier,
            $dummySong->talentIdentifier,
            $dummySong->lyricist,
            $dummySong->composer,
            $dummySong->releaseDate,
            $dummySong->overView,
            $dummySong->musicVideoLink,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn(null);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('findById')
            ->once()
            ->with($dummySong->songIdentifier)
            ->andReturn($dummySong->song);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);
        $this->expectException(PrincipalNotFoundException::class);
        $mergeSong = $this->app->make(MergeSongInterface::class);
        $mergeSong->process($input);
    }

    /**
     * 正常系：AGENCY_ACTORがSongをマージできること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithAgencyActor(): void
    {
        $dummySong = $this->createDummyMergeSong();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $agencyId = (string) $dummySong->agencyIdentifier;
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::AGENCY_ACTOR, $agencyId, [], []);

        $input = new MergeSongInput(
            $dummySong->songIdentifier,
            $dummySong->name,
            $dummySong->agencyIdentifier,
            $dummySong->groupIdentifier,
            $dummySong->talentIdentifier,
            $dummySong->lyricist,
            $dummySong->composer,
            $dummySong->releaseDate,
            $dummySong->overView,
            $dummySong->musicVideoLink,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('findById')
            ->once()
            ->with($dummySong->songIdentifier)
            ->andReturn($dummySong->song);
        $draftSongRepository->shouldReceive('save')
            ->once()
            ->with($dummySong->song)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);

        $mergeSong = $this->app->make(MergeSongInterface::class);
        $mergeSong->process($input);
    }

    /**
     * 正常系：TALENT_ACTORがSongをマージできること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithTalentActor(): void
    {
        $dummySong = $this->createDummyMergeSong();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::TALENT_ACTOR, null, [(string) $dummySong->groupIdentifier], [(string) $dummySong->talentIdentifier]);

        $input = new MergeSongInput(
            $dummySong->songIdentifier,
            $dummySong->name,
            $dummySong->agencyIdentifier,
            $dummySong->groupIdentifier,
            $dummySong->talentIdentifier,
            $dummySong->lyricist,
            $dummySong->composer,
            $dummySong->releaseDate,
            $dummySong->overView,
            $dummySong->musicVideoLink,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('findById')
            ->once()
            ->with($dummySong->songIdentifier)
            ->andReturn($dummySong->song);
        $draftSongRepository->shouldReceive('save')
            ->once()
            ->with($dummySong->song)
            ->andReturn(null);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);

        $mergeSong = $this->app->make(MergeSongInterface::class);
        $mergeSong->process($input);
    }

    /**
     * 異常系：COLLABORATORがSongをマージしようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithCollaborator(): void
    {
        $dummySong = $this->createDummyMergeSong();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::COLLABORATOR, null, [], []);

        $input = new MergeSongInput(
            $dummySong->songIdentifier,
            $dummySong->name,
            $dummySong->agencyIdentifier,
            $dummySong->groupIdentifier,
            $dummySong->talentIdentifier,
            $dummySong->lyricist,
            $dummySong->composer,
            $dummySong->releaseDate,
            $dummySong->overView,
            $dummySong->musicVideoLink,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('findById')
            ->once()
            ->with($dummySong->songIdentifier)
            ->andReturn($dummySong->song);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);

        $this->expectException(UnauthorizedException::class);
        $mergeSong = $this->app->make(MergeSongInterface::class);
        $mergeSong->process($input);
    }

    /**
     * 異常系：NONEロールがSongをマージしようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws SongNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function testProcessWithNoneRole(): void
    {
        $dummySong = $this->createDummyMergeSong();
        $mergedAt = new DateTimeImmutable('2026-01-02 12:00:00');

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), Role::NONE, null, [], []);

        $input = new MergeSongInput(
            $dummySong->songIdentifier,
            $dummySong->name,
            $dummySong->agencyIdentifier,
            $dummySong->groupIdentifier,
            $dummySong->talentIdentifier,
            $dummySong->lyricist,
            $dummySong->composer,
            $dummySong->releaseDate,
            $dummySong->overView,
            $dummySong->musicVideoLink,
            $principalIdentifier,
            $mergedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->with($principalIdentifier)
            ->once()
            ->andReturn($principal);

        $draftSongRepository = Mockery::mock(DraftSongRepositoryInterface::class);
        $draftSongRepository->shouldReceive('findById')
            ->once()
            ->with($dummySong->songIdentifier)
            ->andReturn($dummySong->song);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(DraftSongRepositoryInterface::class, $draftSongRepository);

        $this->expectException(UnauthorizedException::class);
        $mergeSong = $this->app->make(MergeSongInterface::class);
        $mergeSong->process($input);
    }

    /**
     * ダミーデータを作成するヘルパーメソッド
     *
     * @return MergeSongTestData
     */
    private function createDummyMergeSong(): MergeSongTestData
    {
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $publishedSongIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new SongName('TT');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('"TT"는 처음으로 사랑에 빠진 소녀의 어쩔 줄 모르는 마음을 노래한 곡입니다.');
        $coverImagePath = new ImagePath('/resources/public/images/tt.webp');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $status = ApprovalStatus::Pending;

        $song = new DraftSong(
            $songIdentifier,
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $agencyIdentifier,
            $groupIdentifier,
            $talentIdentifier,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
            $status,
        );

        return new MergeSongTestData(
            $songIdentifier,
            $publishedSongIdentifier,
            $translationSetIdentifier,
            $editorIdentifier,
            $language,
            $name,
            $agencyIdentifier,
            $groupIdentifier,
            $talentIdentifier,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $coverImagePath,
            $musicVideoLink,
            $status,
            $song,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class MergeSongTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     */
    public function __construct(
        public SongIdentifier           $songIdentifier,
        public SongIdentifier           $publishedSongIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public PrincipalIdentifier      $editorIdentifier,
        public Language                 $language,
        public SongName                 $name,
        public AgencyIdentifier         $agencyIdentifier,
        public GroupIdentifier          $groupIdentifier,
        public TalentIdentifier         $talentIdentifier,
        public Lyricist                 $lyricist,
        public Composer                 $composer,
        public ReleaseDate              $releaseDate,
        public Overview                 $overView,
        public ImagePath                $coverImagePath,
        public ExternalContentLink      $musicVideoLink,
        public ApprovalStatus           $status,
        public DraftSong                $song,
    ) {
    }
}
