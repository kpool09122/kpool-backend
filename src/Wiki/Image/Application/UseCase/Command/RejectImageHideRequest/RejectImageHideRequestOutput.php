<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RejectImageHideRequest;

use Source\Wiki\Image\Domain\Entity\Image;

class RejectImageHideRequestOutput implements RejectImageHideRequestOutputPort
{
    private ?Image $image = null;

    public function setImage(Image $image): void
    {
        $this->image = $image;
    }

    /**
     * @return array{imageIdentifier: ?string, status: ?string, reviewerComment: ?string, isHidden: ?bool}
     */
    public function toArray(): array
    {
        $hideRequest = $this->image?->latestHideRequest();
        if ($this->image === null || $hideRequest === null) {
            return [
                'imageIdentifier' => null,
                'status' => null,
                'reviewerComment' => null,
                'isHidden' => null,
            ];
        }

        return [
            'imageIdentifier' => (string) $this->image->imageIdentifier(),
            'status' => $hideRequest->status()->value,
            'reviewerComment' => $hideRequest->reviewerComment(),
            'isHidden' => $this->image->isHidden(),
        ];
    }
}
