<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\DeleteVideoLink;

use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkNotFoundException;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;

readonly class DeleteVideoLink implements DeleteVideoLinkInterface
{
    public function __construct(
        private VideoLinkRepositoryInterface $videoLinkRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /**
     * @param DeleteVideoLinkInputPort $input
     * @return void
     * @throws DisallowedException
     * @throws VideoLinkNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function process(DeleteVideoLinkInputPort $input): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $videoLink = $this->videoLinkRepository->findById($input->videoLinkIdentifier());
        if ($videoLink === null) {
            throw new VideoLinkNotFoundException();
        }

        if (! $this->policyEvaluator->evaluate($principal, Action::DELETE_VIDEO_LINK, new Resource(type: $videoLink->resourceType()))) {
            throw new DisallowedException();
        }

        if (! $videoLink->videoUsage()->isAutoCollected()
            && ($videoLink->creatorPrincipalIdentifier() === null
                || (string) $videoLink->creatorPrincipalIdentifier() !== (string) $principal->principalIdentifier())) {
            throw new DisallowedException();
        }

        $this->videoLinkRepository->delete($input->videoLinkIdentifier());
    }
}
