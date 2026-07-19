<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\ApproveImageDeletion;

use Source\Wiki\Image\Domain\Entity\Image;

class ApproveImageDeletionOutput implements ApproveImageDeletionOutputPort
{
    private ?Image $image = null;

    public function setImage(Image $image): void
    {
        $this->image = $image;
    }

    /**
     * @return array{imageIdentifier: ?string, reviewerComment: ?string, isHidden: ?bool}
     */
    public function toArray(): array
    {
        $deletionRequest = $this->image?->latestDeletionRequest();
        if ($this->image === null || $deletionRequest === null) {
            return [
                'imageIdentifier' => null,
                'reviewerComment' => null,
                'isHidden' => null,
            ];
        }

        return [
            'imageIdentifier' => (string) $this->image->imageIdentifier(),
            'reviewerComment' => $deletionRequest->reviewerComment(),
            'isHidden' => $this->image->isHidden(),
        ];
    }
}
