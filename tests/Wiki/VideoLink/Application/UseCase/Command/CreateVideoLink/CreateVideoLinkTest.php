<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink;

use DateTimeImmutable;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\DurationSeconds;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\ThumbnailUrl;
use Source\Wiki\Shared\Domain\ValueObject\TikTokVideoUrl;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Shared\Domain\ValueObject\VideoTitle;
use Source\Wiki\Shared\Domain\ValueObject\YouTubeVideoUrl;
use Source\Wiki\VideoLink\Application\Service\VideoMetadata;
use Source\Wiki\VideoLink\Application\Service\VideoMetadataServiceInterface;
use Source\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink\CreateVideoLinkInput;
use Source\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink\CreateVideoLinkInterface;
use Source\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink\CreateVideoLinkOutput;
use Source\Wiki\VideoLink\Domain\Entity\VideoLink;
use Source\Wiki\VideoLink\Domain\Exception\VideoLinkAlreadyExistsException;
use Source\Wiki\VideoLink\Domain\Factory\VideoLinkFactoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreateVideoLinkTest extends TestCase
{
    public function testCreatesOneVideoWithoutDeletingExistingVideos(): void
    {
        $input = $this->input();
        $this->authorize($input);
        $repository = Mockery::mock(VideoLinkRepositoryInterface::class);
        $repository->shouldReceive('findByResourceAndUrls')->once()->with($input->resourceType(), $input->wikiIdentifier(), [(string) $input->url()])->andReturn([]);
        $repository->shouldNotReceive('deleteByResource');
        $repository->shouldNotReceive('deleteAutoCollectedByResource');
        $repository->shouldReceive('save')->once()->with(Mockery::on(
            static fn (VideoLink $video): bool =>
            $video->creatorPrincipalIdentifier() === $input->principalIdentifier()
            && $video->durationSeconds()?->value() === 253
            && $video->resourceType() === $input->resourceType()
            && $video->wikiIdentifier() === $input->wikiIdentifier()
            && $video->videoUsage() === $input->videoUsage()
            && $video->title() === $input->title()
            && $video->url() === $input->url()
            && $video->thumbnailUrl() === $input->thumbnailUrl()
            && $video->publishedAt() === $input->publishedAt()
        ));
        $this->app()->instance(VideoLinkRepositoryInterface::class, $repository);
        $this->app()->make(CreateVideoLinkInterface::class)->process($input, new CreateVideoLinkOutput());
    }

    public function testDeniedPrincipalCannotWrite(): void
    {
        $input = $this->input();
        $this->authorize($input, false);
        $this->app()->instance(VideoLinkRepositoryInterface::class, Mockery::mock(VideoLinkRepositoryInterface::class));
        $this->expectException(DisallowedException::class);
        $this->app()->make(CreateVideoLinkInterface::class)->process($input, new CreateVideoLinkOutput());
    }

    public function testMissingPrincipalCannotWrite(): void
    {
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->once()->andReturn(null);
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->expectException(PrincipalNotFoundException::class);
        $this->app()->make(CreateVideoLinkInterface::class)->process($this->input(), new CreateVideoLinkOutput());
    }

    public function testDuplicateUrlDoesNotOverwriteVideo(): void
    {
        $input = $this->input();
        $this->authorize($input);
        $repository = Mockery::mock(VideoLinkRepositoryInterface::class);
        $existing = $this->app()->make(VideoLinkFactoryInterface::class)->create($input->resourceType(), $input->wikiIdentifier(), $input->url(), VideoUsage::YOUTUBE_AUTO_VIEW_COUNT, new VideoTitle('Existing'), );
        $repository->shouldReceive('findByResourceAndUrls')->once()->andReturn([$existing]);
        $repository->shouldNotReceive('save');
        $this->app()->instance(VideoLinkRepositoryInterface::class, $repository);
        $this->expectException(VideoLinkAlreadyExistsException::class);
        $this->app()->make(CreateVideoLinkInterface::class)->process($input, new CreateVideoLinkOutput());
    }

    /** @return array<string, array{bool}> */
    public static function invalidWikis(): array
    {
        return ['missing wiki' => [true], 'different resource type' => [false]];
    }

    #[DataProvider('invalidWikis')]
    public function testInvalidWikiCannotWrite(bool $missing): void
    {
        $input = $this->input();
        $principal = new Principal($input->principalIdentifier(), new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->once()->andReturn($principal);
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        /** @var BasicInterface&Mockery\MockInterface $basic */
        $basic = Mockery::mock(BasicInterface::class);
        $wiki = $missing ? null : new Wiki($input->wikiIdentifier(), new TranslationSetIdentifier(StrTestHelper::generateUuid()), new Slug('gr-test'), Language::JAPANESE, ResourceType::GROUP, $basic, new SectionContentCollection(), null, new Version(1));
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')->once()->andReturn($wiki);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $this->app()->instance(VideoLinkRepositoryInterface::class, Mockery::mock(VideoLinkRepositoryInterface::class));
        $this->expectException(WikiNotFoundException::class);
        $this->app()->make(CreateVideoLinkInterface::class)->process($input, new CreateVideoLinkOutput());
    }

    #[DataProvider('tikTokUrls')]
    public function testCreatesTikTokWithServerMetadataAndReportsNewImageForRollback(string $inputUrl): void
    {
        $input = new CreateVideoLinkInput(new PrincipalIdentifier(StrTestHelper::generateUuid()), ResourceType::TALENT, new WikiIdentifier(StrTestHelper::generateUuid()), new TikTokVideoUrl($inputUrl), VideoUsage::SHORT, new VideoTitle('User title'), null);
        $this->authorize($input);
        $metadataService = Mockery::mock(VideoMetadataServiceInterface::class);
        $path = new ImagePath('images/cover.webp');
        $url = new TikTokVideoUrl('https://www.tiktok.com/@scout2015/video/6718335390845095173');
        $metadataService->shouldReceive('resolve')->once()->with((string) $input->url())->andReturn(new VideoMetadata($url, new DurationSeconds(12), $path));
        $this->app()->instance(VideoMetadataServiceInterface::class, $metadataService);
        $repository = Mockery::mock(VideoLinkRepositoryInterface::class);
        $repository->shouldReceive('findByResourceAndUrls')->once()->andReturn([]);
        $repository->shouldReceive('save')->once()->with(Mockery::on(static fn (VideoLink $video): bool => $video->title() === $input->title() && $video->thumbnailPath() === $path && $video->durationSeconds()?->value() === 12 && $video->url() === $url));
        $this->app()->instance(VideoLinkRepositoryInterface::class, $repository);
        $output = new CreateVideoLinkOutput();
        $this->app()->make(CreateVideoLinkInterface::class)->process($input, $output);
        self::assertSame($path, $output->thumbnailPath());
    }

    /** @return list<array{string}> */
    public static function tikTokUrls(): array
    {
        return [
            ['https://vm.tiktok.com/abc/'],
            ['https://vt.tiktok.com/abc/'],
            ['https://www.tiktok.com/@scout2015/video/6718335390845095173'],
        ];
    }

    #[DataProvider('registrationFailures')]
    public function testReportsNewCoverForCompensationOnDuplicateOrDatabaseFailure(bool $duplicate): void
    {
        $input = new CreateVideoLinkInput(new PrincipalIdentifier(StrTestHelper::generateUuid()), ResourceType::TALENT, new WikiIdentifier(StrTestHelper::generateUuid()), new TikTokVideoUrl('https://vt.tiktok.com/abc/'), VideoUsage::SHORT, new VideoTitle('User title'), new DurationSeconds(999), new ThumbnailUrl('https://example.com/ignored.jpg'));
        $this->authorize($input);
        $path = new ImagePath('images/new.webp');
        $url = new TikTokVideoUrl('https://www.tiktok.com/@renamed/video/6718335390845095173');
        $metadataService = Mockery::mock(VideoMetadataServiceInterface::class);
        $metadataService->shouldReceive('resolve')->once()->andReturn(new VideoMetadata($url, new DurationSeconds(12), $path));
        $this->app()->instance(VideoMetadataServiceInterface::class, $metadataService);
        $repository = Mockery::mock(VideoLinkRepositoryInterface::class);
        $existing = $this->app()->make(VideoLinkFactoryInterface::class)->create($input->resourceType(), $input->wikiIdentifier(), $url, VideoUsage::SHORT, $input->title());
        $repository->shouldReceive('findByResourceAndUrls')->once()->with($input->resourceType(), $input->wikiIdentifier(), [(string) $url])->andReturn($duplicate ? [$existing] : []);
        if ($duplicate) {
            $repository->shouldNotReceive('save');
        } else {
            $repository->shouldReceive('save')->once()->with(Mockery::on(static fn (VideoLink $video): bool => $video->thumbnailUrl() === null && $video->durationSeconds()?->value() === 12))->andThrow(new RuntimeException('Database failed'));
        }
        $this->app()->instance(VideoLinkRepositoryInterface::class, $repository);
        $output = new CreateVideoLinkOutput();

        try {
            $this->app()->make(CreateVideoLinkInterface::class)->process($input, $output);
            self::fail('Registration must not succeed.');
        } catch (VideoLinkAlreadyExistsException|RuntimeException $e) {
            self::assertSame($duplicate ? VideoLinkAlreadyExistsException::class : RuntimeException::class, $e::class);
            self::assertSame($path, $output->thumbnailPath());
        }
    }

    /** @return list<array{bool}> */
    public static function registrationFailures(): array
    {
        return [[true], [false]];
    }

    private function input(): CreateVideoLinkInput
    {
        return new CreateVideoLinkInput(new PrincipalIdentifier(StrTestHelper::generateUuid()), ResourceType::TALENT, new WikiIdentifier(StrTestHelper::generateUuid()), new YouTubeVideoUrl('https://www.youtube.com/watch?v=Os_heh8vPfs'), VideoUsage::MUSIC_VIDEO, new VideoTitle('New video'), new DurationSeconds(253), new ThumbnailUrl('https://i.ytimg.com/vi/new/hqdefault.jpg'), new DateTimeImmutable('2026-10-01'));
    }

    private function authorize(CreateVideoLinkInput $input, bool $allowed = true): void
    {
        $principal = new Principal($input->principalIdentifier(), new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->once()->with($input->principalIdentifier())->andReturn($principal);
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        /** @var BasicInterface&Mockery\MockInterface $basic */
        $basic = Mockery::mock(BasicInterface::class);
        $wiki = new Wiki($input->wikiIdentifier(), new TranslationSetIdentifier(StrTestHelper::generateUuid()), new Slug('tl-test'), Language::JAPANESE, ResourceType::TALENT, $basic, new SectionContentCollection(), null, new Version(1));
        $wikiRepository = Mockery::mock(WikiRepositoryInterface::class);
        $wikiRepository->shouldReceive('findById')->with($input->wikiIdentifier())->andReturn($wiki);
        $this->app()->instance(WikiRepositoryInterface::class, $wikiRepository);
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->with($principal, Action::CREATE_VIDEO_LINK, Mockery::on(static fn (Resource $resource): bool => $resource->type() === ResourceType::TALENT))->andReturn($allowed);
        $this->app()->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
    }
}
