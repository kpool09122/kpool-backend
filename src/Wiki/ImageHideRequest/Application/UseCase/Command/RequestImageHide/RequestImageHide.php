<?php

declare(strict_types=1);

namespace Source\Wiki\ImageHideRequest\Application\UseCase\Command\RequestImageHide;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\ImageHideRequest\Application\Exception\ImageHideRequestAlreadyPendingException;
use Source\Wiki\ImageHideRequest\Domain\Factory\ImageHideRequestFactoryInterface;
use Source\Wiki\ImageHideRequest\Domain\Repository\ImageHideRequestRepositoryInterface;

readonly class RequestImageHide implements RequestImageHideInterface
{
    public function __construct(
        private ImageRepositoryInterface $imageRepository,
        private ImageHideRequestRepositoryInterface $imageHideRequestRepository,
        private ImageHideRequestFactoryInterface $imageHideRequestFactory,
    ) {
    }

    /**
     * @param RequestImageHideInputPort $input
     * @param RequestImageHideOutputPort $output
     * @return void
     * @throws ImageNotFoundException
     */
    public function process(RequestImageHideInputPort $input, RequestImageHideOutputPort $output): void
    {
        $image = $this->imageRepository->findById($input->imageIdentifier());
        if ($image === null) {
            throw new ImageNotFoundException();
        }

        if ($this->imageHideRequestRepository->existsPendingByImageId($input->imageIdentifier())) {
            throw new ImageHideRequestAlreadyPendingException();
        }

        $imageHideRequest = $this->imageHideRequestFactory->create(
            $input->imageIdentifier(),
            $input->requesterName(),
            $input->requesterEmail(),
            $input->reason(),
        );

        $this->imageHideRequestRepository->save($imageHideRequest);

        $output->setImageHideRequest($imageHideRequest);
    }
}
