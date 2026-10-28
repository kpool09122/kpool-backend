<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\LikeVideoLink;

use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkAlreadyLikedException;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkNotFoundException;
use Source\Wiki\VideoLink\Domain\Factory\VideoLinkLikeFactoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkLikeRepositoryInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;

readonly class LikeVideoLink implements LikeVideoLinkInterface
{
    public function __construct(
        private VideoLinkRepositoryInterface $videoLinkRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private VideoLinkLikeRepositoryInterface $videoLinkLikeRepository,
        private VideoLinkLikeFactoryInterface $videoLinkLikeFactory,
        private WikiRepositoryInterface $wikiRepository,
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

        $wiki = $this->wikiRepository->findById($videoLink->wikiIdentifier());
        $isOfficialOwner = $videoLink->resourceType() === ResourceType::TALENT
            && $wiki?->resourceType() === ResourceType::TALENT
            && $wiki->isOfficial()
            && ! $principal->isDelegatedPrincipal()
            && (string) $wiki->ownerAccountIdentifier() === (string) $principal->accountIdentifier();
        $this->videoLinkLikeRepository->save($this->videoLinkLikeFactory->create($input->videoLinkIdentifier(), $input->principalIdentifier(), $isOfficialOwner));
    }
}
