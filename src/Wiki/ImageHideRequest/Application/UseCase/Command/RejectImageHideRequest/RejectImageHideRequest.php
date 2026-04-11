<?php

declare(strict_types=1);

namespace Source\Wiki\ImageHideRequest\Application\UseCase\Command\RejectImageHideRequest;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Image\Domain\Service\ImageAuthorizationResourceBuilderInterface;
use Source\Wiki\ImageHideRequest\Application\Exception\ImageHideRequestInvalidStatusException;
use Source\Wiki\ImageHideRequest\Application\Exception\ImageHideRequestNotFoundException;
use Source\Wiki\ImageHideRequest\Domain\Repository\ImageHideRequestRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;

readonly class RejectImageHideRequest implements RejectImageHideRequestInterface
{
    public function __construct(
        private ImageHideRequestRepositoryInterface $imageHideRequestRepository,
        private ImageRepositoryInterface $imageRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private ImageAuthorizationResourceBuilderInterface $imageAuthorizationResourceBuilder,
    ) {
    }

    /**
     * @param RejectImageHideRequestInputPort $input
     * @param RejectImageHideRequestOutputPort $output
     * @return void
     * @throws DisallowedException
     * @throws ImageNotFoundException
     * @throws PrincipalNotFoundException
     */
    public function process(RejectImageHideRequestInputPort $input, RejectImageHideRequestOutputPort $output): void
    {
        $imageHideRequest = $this->imageHideRequestRepository->findById($input->requestIdentifier());
        if ($imageHideRequest === null) {
            throw new ImageHideRequestNotFoundException();
        }

        if (! $imageHideRequest->isPending()) {
            throw new ImageHideRequestInvalidStatusException();
        }

        $image = $this->imageRepository->findById($imageHideRequest->imageIdentifier());
        if ($image === null) {
            throw new ImageNotFoundException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $resource = $this->imageAuthorizationResourceBuilder->buildFromImage($image);
        if (! $this->policyEvaluator->evaluate($principal, Action::REJECT, $resource)) {
            throw new DisallowedException();
        }

        $imageHideRequest->reject($input->principalIdentifier(), $input->reviewerComment());
        $this->imageHideRequestRepository->save($imageHideRequest);

        $output->setImageHideRequest($imageHideRequest);
    }
}
