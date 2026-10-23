<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink;

use DateTimeImmutable;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink\CreateVideoLinkInput;
use Source\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink\CreateVideoLinkInterface;
use Source\Wiki\VideoLink\Domain\Entity\VideoLink;
use Source\Wiki\VideoLink\Domain\Exception\VideoLinkAlreadyExistsException;
use Source\Wiki\VideoLink\Domain\Factory\VideoLinkFactoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\DisplayOrder;
use Source\Wiki\VideoLink\Domain\ValueObject\DurationSeconds;
use Source\Wiki\VideoLink\Domain\ValueObject\ThumbnailUrl;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoTitle;
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
            && $video->url() === $input->url()
            && $video->thumbnailUrl() === $input->thumbnailUrl()
            && $video->publishedAt() === $input->publishedAt()
        ));
        $this->app()->instance(VideoLinkRepositoryInterface::class, $repository);
        $this->app()->make(CreateVideoLinkInterface::class)->process($input);
    }

    public function testDeniedPrincipalCannotWrite(): void
    {
        $input = $this->input();
        $this->authorize($input, false);
        $this->app()->instance(VideoLinkRepositoryInterface::class, Mockery::mock(VideoLinkRepositoryInterface::class));
        $this->expectException(DisallowedException::class);
        $this->app()->make(CreateVideoLinkInterface::class)->process($input);
    }

    public function testMissingPrincipalCannotWrite(): void
    {
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->once()->andReturn(null);
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->expectException(PrincipalNotFoundException::class);
        $this->app()->make(CreateVideoLinkInterface::class)->process($this->input());
    }

    public function testDuplicateUrlDoesNotOverwriteVideo(): void
    {
        $input = $this->input();
        $this->authorize($input);
        $repository = Mockery::mock(VideoLinkRepositoryInterface::class);
        $existing = $this->app()->make(VideoLinkFactoryInterface::class)->create($input->resourceType(), $input->wikiIdentifier(), $input->url(), VideoUsage::YOUTUBE_AUTO_VIEW_COUNT, new VideoTitle('Existing'), new DisplayOrder(1));
        $repository->shouldReceive('findByResourceAndUrls')->once()->andReturn([$existing]);
        $repository->shouldNotReceive('save');
        $this->app()->instance(VideoLinkRepositoryInterface::class, $repository);
        $this->expectException(VideoLinkAlreadyExistsException::class);
        $this->app()->make(CreateVideoLinkInterface::class)->process($input);
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
        $this->app()->make(CreateVideoLinkInterface::class)->process($input);
    }

    private function input(): CreateVideoLinkInput
    {
        return new CreateVideoLinkInput(new PrincipalIdentifier(StrTestHelper::generateUuid()), ResourceType::TALENT, new WikiIdentifier(StrTestHelper::generateUuid()), new ExternalContentLink('https://www.youtube.com/watch?v=new'), VideoUsage::MUSIC_VIDEO, new VideoTitle('New video'), new DisplayOrder(1), new DurationSeconds(253), new ThumbnailUrl('https://i.ytimg.com/vi/new/hqdefault.jpg'), new DateTimeImmutable('2026-10-01'));
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
        $policyEvaluator->shouldReceive('evaluate')->once()->with($principal, Action::SAVE_VIDEO_LINKS, Mockery::on(static fn (Resource $resource): bool => $resource->type() === ResourceType::TALENT))->andReturn($allowed);
        $this->app()->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
    }
}
