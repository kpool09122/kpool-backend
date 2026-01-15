<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RejectImage;

use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Image\Domain\Repository\DraftImageRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

readonly class RejectImage implements RejectImageInterface
{
    public function __construct(
        private DraftImageRepositoryInterface $draftImageRepository,
    ) {
    }

    public function process(RejectImageInputPort $input): DraftImage
    {
        $draftImage = $this->draftImageRepository->findById($input->imageIdentifier());
        if ($draftImage === null) {
            throw new ImageNotFoundException();
        }

        if ($draftImage->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        $draftImage->setStatus(ApprovalStatus::Rejected);
        $this->draftImageRepository->save($draftImage);

        return $draftImage;
    }
}
