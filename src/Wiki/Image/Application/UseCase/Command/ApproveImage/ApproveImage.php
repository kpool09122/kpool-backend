<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\ApproveImage;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Factory\ImageFactoryInterface;
use Source\Wiki\Image\Domain\Factory\ImageSnapshotFactoryInterface;
use Source\Wiki\Image\Domain\Repository\DraftImageRepositoryInterface;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Image\Domain\Repository\ImageSnapshotRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

readonly class ApproveImage implements ApproveImageInterface
{
    public function __construct(
        private DraftImageRepositoryInterface $draftImageRepository,
        private ImageRepositoryInterface $imageRepository,
        private ImageFactoryInterface $imageFactory,
        private ImageSnapshotFactoryInterface $imageSnapshotFactory,
        private ImageSnapshotRepositoryInterface $imageSnapshotRepository,
    ) {
    }

    public function process(ApproveImageInputPort $input): Image
    {
        $draftImage = $this->draftImageRepository->findById($input->imageIdentifier());
        if ($draftImage === null) {
            throw new ImageNotFoundException();
        }

        if ($draftImage->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        $publishedImageIdentifier = $draftImage->publishedImageIdentifier();

        if ($publishedImageIdentifier !== null) {
            // 既存Imageの更新
            $existingImage = $this->imageRepository->findById($publishedImageIdentifier);
            if ($existingImage !== null) {
                // スナップショット作成
                $snapshot = $this->imageSnapshotFactory->create(
                    $existingImage,
                    $existingImage->resourceIdentifier(),
                );
                $this->imageSnapshotRepository->save($snapshot);

                // 既存Imageを更新
                $existingImage->setImagePath($draftImage->imagePath());
                $existingImage->setImageUsage($draftImage->imageUsage());
                $existingImage->setDisplayOrder($draftImage->displayOrder());
                $existingImage->setSourceUrl($draftImage->sourceUrl());
                $existingImage->setSourceName($draftImage->sourceName());
                $existingImage->setAltText($draftImage->altText());

                $this->imageRepository->save($existingImage);
                $this->draftImageRepository->delete($draftImage->imageIdentifier());

                return $existingImage;
            }
        }

        // 新規Image作成
        $image = $this->imageFactory->create(
            $draftImage->resourceType(),
            $draftImage->draftResourceIdentifier(),
            $draftImage->imagePath(),
            $draftImage->imageUsage(),
            $draftImage->displayOrder(),
            $draftImage->sourceUrl(),
            $draftImage->sourceName(),
            $draftImage->altText(),
        );

        $this->imageRepository->save($image);
        $this->draftImageRepository->delete($draftImage->imageIdentifier());

        return $image;
    }
}
