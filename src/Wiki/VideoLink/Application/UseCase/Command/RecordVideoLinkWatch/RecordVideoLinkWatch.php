<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\RecordVideoLinkWatch;

use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkNotFoundException;
use Source\Wiki\VideoLink\Domain\Factory\VideoLinkWatchFactoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkWatchRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;

final readonly class RecordVideoLinkWatch implements RecordVideoLinkWatchInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private VideoLinkRepositoryInterface $videoLinkRepository,
        private WikiRepositoryInterface $wikiRepository,
        private VideoLinkWatchRepositoryInterface $videoLinkWatchRepository,
        private VideoLinkWatchFactoryInterface $videoLinkWatchFactory,
    ) {
    }

    public function process(RecordVideoLinkWatchInputPort $input): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        $video = $this->videoLinkRepository->findById($input->videoLinkIdentifier());
        if ($video === null) {
            throw new VideoLinkNotFoundException();
        }
        $wiki = $this->wikiRepository->findById($video->wikiIdentifier());
        $resource = new Resource(
            type: $video->resourceType(),
            isOfficial: $wiki?->isOfficial() ?? false,
            wikiResourceType: $wiki?->resourceType(),
            ownerAccountIdentifier: $wiki?->ownerAccountIdentifier(),
        );
        if (! $this->policyEvaluator->evaluate($principal, Action::RECORD_VIDEO_LINK_WATCH, $resource)) {
            throw new DisallowedException();
        }
        if ($this->videoLinkWatchRepository->findByVideoLinkAndOwnerAccount($video->videoLinkIdentifier(), $principal->accountIdentifier()) !== null) {
            return;
        }
        $videoLinkWatch = $this->videoLinkWatchFactory->create($video->videoLinkIdentifier(), $principal->accountIdentifier(), $principal->principalIdentifier());
        $this->videoLinkWatchRepository->save($videoLinkWatch);
    }
}
