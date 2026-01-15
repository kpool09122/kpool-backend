<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\UploadImage;

use Source\Shared\Application\Exception\InvalidBase64ImageException;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Image\Domain\Factory\DraftImageFactoryInterface;
use Source\Wiki\Image\Domain\Repository\DraftImageRepositoryInterface;

readonly class UploadImage implements UploadImageInterface
{
    public function __construct(
        private ImageServiceInterface $imageService,
        private DraftImageFactoryInterface $draftImageFactory,
        private DraftImageRepositoryInterface $draftImageRepository,
    ) {
    }

    /**
     * @param UploadImageInputPort $input
     * @return DraftImage
     * @throws InvalidBase64ImageException
     */
    public function process(UploadImageInputPort $input): DraftImage
    {
        $uploadResult = $this->imageService->upload($input->base64EncodedImage());

        $draftImage = $this->draftImageFactory->create(
            $input->publishedImageIdentifier(),
            $input->resourceType(),
            $input->draftResourceIdentifier(),
            $input->principalIdentifier(),
            $uploadResult->resized,
            $input->imageUsage(),
            $input->displayOrder(),
            $input->sourceUrl(),
            $input->sourceName(),
            $input->altText(),
            $input->agreedToTermsAt(),
        );

        $this->draftImageRepository->save($draftImage);

        return $draftImage;
    }
}
