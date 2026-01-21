<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Command\SaveVideoLinks;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Application\UseCase\Command\SaveVideoLinks\SaveVideoLinks;
use Source\Wiki\VideoLink\Application\UseCase\Command\SaveVideoLinks\SaveVideoLinksInput;
use Source\Wiki\VideoLink\Application\UseCase\Command\SaveVideoLinks\SaveVideoLinksInterface;
use Source\Wiki\VideoLink\Application\UseCase\Command\SaveVideoLinks\VideoLinkData;
use Source\Wiki\VideoLink\Domain\Entity\VideoLink;
use Source\Wiki\VideoLink\Domain\Factory\VideoLinkFactoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SaveVideoLinksTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $videoLinkFactory = Mockery::mock(VideoLinkFactoryInterface::class);
        $videoLinkRepository = Mockery::mock(VideoLinkRepositoryInterface::class);

        $this->app->instance(VideoLinkFactoryInterface::class, $videoLinkFactory);
        $this->app->instance(VideoLinkRepositoryInterface::class, $videoLinkRepository);

        $saveVideoLinks = $this->app->make(SaveVideoLinksInterface::class);
        $this->assertInstanceOf(SaveVideoLinks::class, $saveVideoLinks);
    }

    /**
     * 正常系：既存のVideoLinksを削除して新しいVideoLinksを保存すること.
     *
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::TALENT;
        $resourceIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());

        $videoLinkData1 = new VideoLinkData(
            new ExternalContentLink('https://www.youtube.com/watch?v=video1'),
            VideoUsage::MUSIC_VIDEO,
            'Music Video 1',
            1,
        );
        $videoLinkData2 = new VideoLinkData(
            new ExternalContentLink('https://www.youtube.com/watch?v=video2'),
            VideoUsage::LIVE,
            'Live Performance',
            2,
        );

        $input = new SaveVideoLinksInput(
            $principalIdentifier,
            $resourceType,
            $resourceIdentifier,
            [$videoLinkData1, $videoLinkData2],
        );

        $videoLink1 = new VideoLink(
            new VideoLinkIdentifier(StrTestHelper::generateUuid()),
            $resourceType,
            $resourceIdentifier,
            $videoLinkData1->url,
            $videoLinkData1->videoUsage,
            $videoLinkData1->title,
            null,
            null,
            $videoLinkData1->displayOrder,
            new DateTimeImmutable(),
        );

        $videoLink2 = new VideoLink(
            new VideoLinkIdentifier(StrTestHelper::generateUuid()),
            $resourceType,
            $resourceIdentifier,
            $videoLinkData2->url,
            $videoLinkData2->videoUsage,
            $videoLinkData2->title,
            null,
            null,
            $videoLinkData2->displayOrder,
            new DateTimeImmutable(),
        );

        $videoLinkFactory = Mockery::mock(VideoLinkFactoryInterface::class);
        $videoLinkFactory->shouldReceive('create')
            ->once()
            ->with(
                $resourceType,
                $resourceIdentifier,
                $videoLinkData1->url,
                $videoLinkData1->videoUsage,
                $videoLinkData1->title,
                $videoLinkData1->displayOrder,
            )
            ->andReturn($videoLink1);

        $videoLinkFactory->shouldReceive('create')
            ->once()
            ->with(
                $resourceType,
                $resourceIdentifier,
                $videoLinkData2->url,
                $videoLinkData2->videoUsage,
                $videoLinkData2->title,
                $videoLinkData2->displayOrder,
            )
            ->andReturn($videoLink2);

        $videoLinkRepository = Mockery::mock(VideoLinkRepositoryInterface::class);
        $videoLinkRepository->shouldReceive('deleteByResource')
            ->once()
            ->with($resourceType, $resourceIdentifier);

        $videoLinkRepository->shouldReceive('save')
            ->once()
            ->with($videoLink1);

        $videoLinkRepository->shouldReceive('save')
            ->once()
            ->with($videoLink2);

        $this->app->instance(VideoLinkFactoryInterface::class, $videoLinkFactory);
        $this->app->instance(VideoLinkRepositoryInterface::class, $videoLinkRepository);

        $saveVideoLinks = $this->app->make(SaveVideoLinksInterface::class);
        $saveVideoLinks->process($input);
    }

    /**
     * 正常系：空のVideoLinksリストを渡すと既存のVideoLinksのみ削除されること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessWithEmptyList(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::TALENT;
        $resourceIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());

        $input = new SaveVideoLinksInput(
            $principalIdentifier,
            $resourceType,
            $resourceIdentifier,
            [],
        );

        $videoLinkFactory = Mockery::mock(VideoLinkFactoryInterface::class);
        $videoLinkFactory->shouldNotReceive('create');

        $videoLinkRepository = Mockery::mock(VideoLinkRepositoryInterface::class);
        $videoLinkRepository->shouldReceive('deleteByResource')
            ->once()
            ->with($resourceType, $resourceIdentifier);

        $videoLinkRepository->shouldNotReceive('save');

        $this->app->instance(VideoLinkFactoryInterface::class, $videoLinkFactory);
        $this->app->instance(VideoLinkRepositoryInterface::class, $videoLinkRepository);

        $saveVideoLinks = $this->app->make(SaveVideoLinksInterface::class);
        $saveVideoLinks->process($input);
    }
}
