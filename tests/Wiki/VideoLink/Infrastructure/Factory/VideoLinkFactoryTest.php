<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Domain\Factory\VideoLinkFactoryInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\VideoLink\Infrastructure\Factory\VideoLinkFactory;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class VideoLinkFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(VideoLinkFactoryInterface::class);
        $this->assertInstanceOf(VideoLinkFactory::class, $factory);
    }

    /**
     * 正常系: VideoLink Entityが正しく作成されること.
     *
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $resourceType = ResourceType::TALENT;
        $resourceIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());
        $url = new ExternalContentLink('https://www.youtube.com/watch?v=test123');
        $videoUsage = VideoUsage::MUSIC_VIDEO;
        $title = 'Test Music Video';
        $displayOrder = 1;

        $factory = $this->app->make(VideoLinkFactoryInterface::class);
        $videoLink = $factory->create(
            $resourceType,
            $resourceIdentifier,
            $url,
            $videoUsage,
            $title,
            $displayOrder,
        );

        $this->assertTrue(UuidValidator::isValid((string) $videoLink->videoLinkIdentifier()));
        $this->assertSame($resourceType, $videoLink->resourceType());
        $this->assertSame((string) $resourceIdentifier, (string) $videoLink->resourceIdentifier());
        $this->assertSame((string) $url, (string) $videoLink->url());
        $this->assertSame($videoUsage, $videoLink->videoUsage());
        $this->assertSame($title, $videoLink->title());
        $this->assertSame($displayOrder, $videoLink->displayOrder());
    }
}
