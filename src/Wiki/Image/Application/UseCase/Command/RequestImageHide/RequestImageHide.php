<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RequestImageHide;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;

readonly class RequestImageHide implements RequestImageHideInterface
{
    public function __construct(
        private ImageRepositoryInterface $imageRepository,
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

        $image->requestHide(
            $input->requesterName(),
            $input->requesterEmail(),
            $input->reason(),
        );

        $this->imageRepository->save($image);

        $output->setImage($image);
    }
}
