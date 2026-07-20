<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RejectImageDeletion;

use Source\Wiki\Image\Domain\Entity\Image;

class RejectImageDeletionOutput implements RejectImageDeletionOutputPort
{
    private ?Image $image = null;

    public function setImage(Image $image): void
    {
        $this->image = $image;
    }

    /**
     * @return array{imageIdentifier: ?string, rejectReason: ?string, isHidden: ?bool}
     */
    public function toArray(): array
    {
        $deletionRequest = $this->image?->latestDeletionRequest();
        if ($this->image === null || $deletionRequest === null) {
            return [
                'imageIdentifier' => null,
                'rejectReason' => null,
                'isHidden' => null,
            ];
        }

        return [
            'imageIdentifier' => (string) $this->image->imageIdentifier(),
            'rejectReason' => $deletionRequest->rejectReason(),
            'isHidden' => $this->image->isHidden(),
        ];
    }
}
