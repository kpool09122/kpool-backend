<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\UnlikeVideoLink;

use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkNotFoundException;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkLikeRepositoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;

readonly class UnlikeVideoLink implements UnlikeVideoLinkInterface
{
    public function __construct(
        private VideoLinkRepositoryInterface $videoLinkRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private VideoLinkLikeRepositoryInterface $videoLinkLikeRepository,
    ) {
    }

    /**
     * @param UnlikeVideoLinkInputPort $input
     * @return void
     * @throws DisallowedException
     * @throws VideoLinkNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function process(UnlikeVideoLinkInputPort $input): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $videoLink = $this->videoLinkRepository->findById($input->videoLinkIdentifier());
        if ($videoLink === null) {
            throw new VideoLinkNotFoundException();
        }

        if (! $this->policyEvaluator->evaluate($principal, Action::UNLIKE_VIDEO_LINK, new Resource(type: $videoLink->resourceType()))) {
            throw new DisallowedException();
        }

        $this->videoLinkLikeRepository->deleteByVideoLinkAndPrincipal($input->videoLinkIdentifier(), $input->principalIdentifier());
    }
}
