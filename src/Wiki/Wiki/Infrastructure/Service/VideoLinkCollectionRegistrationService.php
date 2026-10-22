<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Infrastructure\Service;

use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLinkAutoCollection\Domain\Factory\VideoLinkCollectionStatusFactoryInterface;
use Source\Wiki\VideoLinkAutoCollection\Domain\Repository\VideoLinkCollectionStatusRepositoryInterface;
use Source\Wiki\VideoLinkAutoCollection\Domain\Service\VideoLinkCollectionPolicy;
use Source\Wiki\Wiki\Application\Service\VideoLinkCollectionRegistrationServiceInterface;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class VideoLinkCollectionRegistrationService implements VideoLinkCollectionRegistrationServiceInterface
{
    public function __construct(
        private VideoLinkCollectionStatusRepositoryInterface $videoLinkCollectionStatusRepository,
        private VideoLinkCollectionStatusFactoryInterface $videoLinkCollectionStatusFactory,
    ) {
    }

    public function register(ResourceType $resourceType, WikiIdentifier $wikiIdentifier): void
    {
        if (! VideoLinkCollectionPolicy::supports($resourceType)) {
            return;
        }

        if ($this->videoLinkCollectionStatusRepository->findByResource($resourceType, $wikiIdentifier) !== null) {
            return;
        }

        $this->videoLinkCollectionStatusRepository->save($this->videoLinkCollectionStatusFactory->create(
            $resourceType,
            $wikiIdentifier,
        ));
    }
}
