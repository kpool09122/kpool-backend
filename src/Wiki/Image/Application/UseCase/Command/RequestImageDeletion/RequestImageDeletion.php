<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RequestImageDeletion;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;

readonly class RequestImageDeletion implements RequestImageDeletionInterface
{
    public function __construct(
        private ImageRepositoryInterface $imageRepository,
    ) {
    }

    /**
     * @param RequestImageDeletionInputPort $input
     * @param RequestImageDeletionOutputPort $output
     * @return void
     * @throws ImageNotFoundException
     */
    public function process(RequestImageDeletionInputPort $input, RequestImageDeletionOutputPort $output): void
    {
        $image = $this->imageRepository->findById($input->imageIdentifier());
        if ($image === null) {
            throw new ImageNotFoundException();
        }

        $image->requestDeletion(
            $input->requesterName(),
            $input->requesterEmail(),
            $input->reason(),
        );

        $this->imageRepository->save($image);

        $output->setImage($image);
    }
}
