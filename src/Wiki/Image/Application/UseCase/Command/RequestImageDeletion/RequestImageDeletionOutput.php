<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RequestImageDeletion;

use Source\Wiki\Image\Domain\Entity\Image;

class RequestImageDeletionOutput implements RequestImageDeletionOutputPort
{
    private ?Image $image = null;

    public function setImage(Image $image): void
    {
        $this->image = $image;
    }

    /**
     * @return array{imageIdentifier: ?string, requesterName: ?string, requesterEmail: ?string, reason: ?string, isHidden: ?bool}
     */
    public function toArray(): array
    {
        $deletionRequest = $this->image?->pendingDeletionRequest();
        if ($this->image === null || $deletionRequest === null) {
            return [
                'imageIdentifier' => null,
                'requesterName' => null,
                'requesterEmail' => null,
                'reason' => null,
                'isHidden' => null,
            ];
        }

        return [
            'imageIdentifier' => (string) $this->image->imageIdentifier(),
            'requesterName' => $deletionRequest->requesterName(),
            'requesterEmail' => $deletionRequest->requesterEmail(),
            'reason' => $deletionRequest->reason(),
            'isHidden' => $this->image->isHidden(),
        ];
    }
}
