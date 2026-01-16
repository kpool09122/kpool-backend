<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\DeleteImage;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Domain\Repository\DraftImageRepositoryInterface;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;

readonly class DeleteImage implements DeleteImageInterface
{
    public function __construct(
        private DraftImageRepositoryInterface $draftImageRepository,
        private ImageRepositoryInterface $imageRepository,
    ) {
    }

    public function process(DeleteImageInputPort $input): void
    {
        if ($input->isDraft()) {
            $draftImage = $this->draftImageRepository->findById($input->imageIdentifier());
            if ($draftImage === null) {
                throw new ImageNotFoundException();
            }

            $this->draftImageRepository->delete($input->imageIdentifier());
        } else {
            $image = $this->imageRepository->findById($input->imageIdentifier());
            if ($image === null) {
                throw new ImageNotFoundException();
            }

            $this->imageRepository->delete($input->imageIdentifier());
        }
    }
}
