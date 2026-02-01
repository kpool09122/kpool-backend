<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\ApproveImage;

use DateTimeImmutable;
use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Factory\ImageFactoryInterface;
use Source\Wiki\Image\Domain\Factory\ImageSnapshotFactoryInterface;
use Source\Wiki\Image\Domain\Repository\DraftImageRepositoryInterface;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Image\Domain\Repository\ImageSnapshotRepositoryInterface;
use Source\Wiki\Image\Domain\Service\ImageAuthorizationResourceBuilderInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

readonly class ApproveImage implements ApproveImageInterface
{
    public function __construct(
        private DraftImageRepositoryInterface $draftImageRepository,
        private ImageRepositoryInterface $imageRepository,
        private ImageFactoryInterface $imageFactory,
        private ImageSnapshotFactoryInterface $imageSnapshotFactory,
        private ImageSnapshotRepositoryInterface $imageSnapshotRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private ImageAuthorizationResourceBuilderInterface $imageAuthorizationResourceBuilder,
    ) {
    }

    /**
     * @param ApproveImageInputPort $input
     * @return Image
     * @throws DisallowedException
     * @throws ImageNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function process(ApproveImageInputPort $input): Image
    {
        $draftImage = $this->draftImageRepository->findById($input->imageIdentifier());
        if ($draftImage === null) {
            throw new ImageNotFoundException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $resource = $this->imageAuthorizationResourceBuilder->buildFromDraftImage($draftImage);
        if (! $this->policyEvaluator->evaluate($principal, Action::APPROVE, $resource)) {
            throw new DisallowedException();
        }

        if ($draftImage->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        $publishedImageIdentifier = $draftImage->publishedImageIdentifier();

        $now = new DateTimeImmutable();

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
                $existingImage->setApproverIdentifier($input->principalIdentifier());
                $existingImage->setApprovedAt($now);
                $existingImage->setUpdaterIdentifier($input->principalIdentifier());
                $existingImage->setUpdatedAt($now);

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
            $draftImage->uploaderIdentifier(),
            $input->principalIdentifier(),
            $now,
        );

        $this->imageRepository->save($image);
        $this->draftImageRepository->delete($draftImage->imageIdentifier());

        return $image;
    }
}
