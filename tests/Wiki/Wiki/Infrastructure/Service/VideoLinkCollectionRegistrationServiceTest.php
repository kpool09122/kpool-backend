<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Service;

use DateTimeImmutable;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLinkAutoCollection\Domain\Entity\VideoLinkCollectionStatus;
use Source\Wiki\VideoLinkAutoCollection\Domain\Factory\VideoLinkCollectionStatusFactoryInterface;
use Source\Wiki\VideoLinkAutoCollection\Domain\Repository\VideoLinkCollectionStatusRepositoryInterface;
use Source\Wiki\VideoLinkAutoCollection\Domain\ValueObject\VideoLinkCollectionStatusIdentifier;
use Source\Wiki\Wiki\Application\Service\VideoLinkCollectionRegistrationServiceInterface;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class VideoLinkCollectionRegistrationServiceTest extends TestCase
{
    #[DataProvider('supportedResourceTypes')]
    public function testRegistersMissingResourceAsUncollected(ResourceType $resourceType): void
    {
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $videoLinkCollectionStatusRepository = Mockery::mock(VideoLinkCollectionStatusRepositoryInterface::class);
        $videoLinkCollectionStatusRepository->shouldReceive('findByResource')->once()
            ->with($resourceType, $wikiIdentifier)->andReturn(null);
        $videoLinkCollectionStatusRepository->shouldReceive('save')->once()
            ->with(Mockery::on(static fn (VideoLinkCollectionStatus $status): bool =>
                $status->resourceType() === $resourceType
                && $status->wikiIdentifier() === $wikiIdentifier
                && $status->lastCollectedAt() === null));
        $this->app()->instance(VideoLinkCollectionStatusRepositoryInterface::class, $videoLinkCollectionStatusRepository);

        $this->app()->make(VideoLinkCollectionRegistrationServiceInterface::class)->register($resourceType, $wikiIdentifier);
    }

    /** @return array<string, array{ResourceType}> */
    public static function supportedResourceTypes(): array
    {
        return [
            'group' => [ResourceType::GROUP],
            'song' => [ResourceType::SONG],
            'talent' => [ResourceType::TALENT],
        ];
    }

    public function testPreservesExistingStatusAndCollectionTimestamp(): void
    {
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $lastCollectedAt = new DateTimeImmutable('2026-01-01');
        $status = new VideoLinkCollectionStatus(
            new VideoLinkCollectionStatusIdentifier(StrTestHelper::generateUuid()),
            ResourceType::GROUP,
            $wikiIdentifier,
            $lastCollectedAt,
            new DateTimeImmutable('2025-01-01'),
        );
        $videoLinkCollectionStatusRepository = Mockery::mock(VideoLinkCollectionStatusRepositoryInterface::class);
        $videoLinkCollectionStatusRepository->shouldReceive('findByResource')->once()
            ->with(ResourceType::GROUP, $wikiIdentifier)->andReturn($status);
        $videoLinkCollectionStatusRepository->shouldNotReceive('save');
        $videoLinkCollectionStatusFactory = Mockery::mock(VideoLinkCollectionStatusFactoryInterface::class);
        $videoLinkCollectionStatusFactory->shouldNotReceive('create');
        $this->app()->instance(VideoLinkCollectionStatusRepositoryInterface::class, $videoLinkCollectionStatusRepository);
        $this->app()->instance(VideoLinkCollectionStatusFactoryInterface::class, $videoLinkCollectionStatusFactory);

        $this->app()->make(VideoLinkCollectionRegistrationServiceInterface::class)->register(ResourceType::GROUP, $wikiIdentifier);

        $this->assertSame($lastCollectedAt, $status->lastCollectedAt());
    }

    public function testDoesNotRegisterUnsupportedResource(): void
    {
        $videoLinkCollectionStatusRepository = Mockery::mock(VideoLinkCollectionStatusRepositoryInterface::class);
        $videoLinkCollectionStatusRepository->shouldNotReceive('findByResource');
        $videoLinkCollectionStatusRepository->shouldNotReceive('save');
        $videoLinkCollectionStatusFactory = Mockery::mock(VideoLinkCollectionStatusFactoryInterface::class);
        $videoLinkCollectionStatusFactory->shouldNotReceive('create');
        $this->app()->instance(VideoLinkCollectionStatusRepositoryInterface::class, $videoLinkCollectionStatusRepository);
        $this->app()->instance(VideoLinkCollectionStatusFactoryInterface::class, $videoLinkCollectionStatusFactory);

        $this->app()->make(VideoLinkCollectionRegistrationServiceInterface::class)->register(
            ResourceType::AGENCY,
            new WikiIdentifier(StrTestHelper::generateUuid()),
        );
    }
}
