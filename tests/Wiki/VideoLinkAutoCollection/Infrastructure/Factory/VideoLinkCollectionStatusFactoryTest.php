<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLinkAutoCollection\Infrastructure\Factory;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLinkAutoCollection\Domain\Factory\VideoLinkCollectionStatusFactoryInterface;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class VideoLinkCollectionStatusFactoryTest extends TestCase
{
    /**
     * 正常系: 収集状態エンティティを作成できること.
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function testCreate(): void
    {
        $resourceId = StrTestHelper::generateUuid();

        $factory = $this->app->make(VideoLinkCollectionStatusFactoryInterface::class);

        $status = $factory->create(
            ResourceType::TALENT,
            new ResourceIdentifier($resourceId),
        );

        $this->assertTrue(UuidValidator::isValid((string) $status->identifier()));
        $this->assertSame(ResourceType::TALENT, $status->resourceType());
        $this->assertSame($resourceId, (string) $status->resourceIdentifier());
        $this->assertNull($status->lastCollectedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $status->createdAt());
    }

    /**
     * 正常系: 異なるリソースタイプでも作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreateWithDifferentResourceTypes(): void
    {
        $factory = $this->app->make(VideoLinkCollectionStatusFactoryInterface::class);

        $talentStatus = $factory->create(
            ResourceType::TALENT,
            new ResourceIdentifier(StrTestHelper::generateUuid()),
        );
        $groupStatus = $factory->create(
            ResourceType::GROUP,
            new ResourceIdentifier(StrTestHelper::generateUuid()),
        );
        $songStatus = $factory->create(
            ResourceType::SONG,
            new ResourceIdentifier(StrTestHelper::generateUuid()),
        );

        $this->assertSame(ResourceType::TALENT, $talentStatus->resourceType());
        $this->assertSame(ResourceType::GROUP, $groupStatus->resourceType());
        $this->assertSame(ResourceType::SONG, $songStatus->resourceType());
    }
}
