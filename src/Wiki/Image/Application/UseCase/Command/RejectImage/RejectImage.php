<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RejectImage;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Domain\Repository\DraftImageRepositoryInterface;
use Source\Wiki\Image\Domain\Service\ImageAuthorizationResourceBuilderInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

readonly class RejectImage implements RejectImageInterface
{
    public function __construct(
        private DraftImageRepositoryInterface $draftImageRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private ImageAuthorizationResourceBuilderInterface $imageAuthorizationResourceBuilder,
    ) {
    }

    /**
     * @param RejectImageInputPort $input
     * @param RejectImageOutputPort $output
     * @return void
     * @throws DisallowedException
     * @throws ImageNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function process(RejectImageInputPort $input, RejectImageOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $draftImage = $this->draftImageRepository->findById($input->imageIdentifier());
        if ($draftImage === null) {
            throw new ImageNotFoundException();
        }

        $resource = $this->imageAuthorizationResourceBuilder->buildFromDraftImage($draftImage);
        if (! $this->policyEvaluator->evaluate($principal, Action::REJECT, $resource)) {
            throw new DisallowedException();
        }

        if ($draftImage->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        $draftImage->setStatus(ApprovalStatus::Rejected);
        $this->draftImageRepository->save($draftImage);

        $output->setDraftImage($draftImage);
    }
}
