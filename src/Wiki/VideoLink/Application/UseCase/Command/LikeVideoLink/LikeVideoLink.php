<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\LikeVideoLink;

use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkAlreadyLikedException;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkNotFoundException;
use Source\Wiki\VideoLink\Domain\Factory\VideoLinkLikeFactoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkLikeRepositoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;

readonly class LikeVideoLink implements LikeVideoLinkInterface
{
    public function __construct(
        private VideoLinkRepositoryInterface $videoLinkRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private VideoLinkLikeRepositoryInterface $videoLinkLikeRepository,
        private VideoLinkLikeFactoryInterface $videoLinkLikeFactory,
    ) {
    }

    /**
     * @param LikeVideoLinkInputPort $input
     * @return void
     * @throws DisallowedException
     * @throws VideoLinkAlreadyLikedException
     * @throws VideoLinkNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function process(LikeVideoLinkInputPort $input): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $videoLink = $this->videoLinkRepository->findById($input->videoLinkIdentifier());
        if ($videoLink === null) {
            throw new VideoLinkNotFoundException();
        }

        if (! $this->policyEvaluator->evaluate($principal, Action::LIKE_VIDEO_LINK, new Resource(type: $videoLink->resourceType()))) {
            throw new DisallowedException();
        }

        if ($this->videoLinkLikeRepository->findByVideoLinkAndPrincipal($input->videoLinkIdentifier(), $input->principalIdentifier()) !== null) {
            throw new VideoLinkAlreadyLikedException();
        }

        $this->videoLinkLikeRepository->save($this->videoLinkLikeFactory->create($input->videoLinkIdentifier(), $input->principalIdentifier()));
    }
}
