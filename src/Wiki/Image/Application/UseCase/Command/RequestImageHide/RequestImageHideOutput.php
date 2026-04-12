<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RequestImageHide;

use Source\Wiki\Image\Domain\Entity\Image;

class RequestImageHideOutput implements RequestImageHideOutputPort
{
    private ?Image $image = null;

    public function setImage(Image $image): void
    {
        $this->image = $image;
    }

    /**
     * @return array{imageIdentifier: ?string, requesterName: ?string, requesterEmail: ?string, reason: ?string, status: ?string}
     */
    public function toArray(): array
    {
        $hideRequest = $this->image?->pendingHideRequest();
        if ($this->image === null || $hideRequest === null) {
            return [
                'imageIdentifier' => null,
                'requesterName' => null,
                'requesterEmail' => null,
                'reason' => null,
                'status' => null,
            ];
        }

        return [
            'imageIdentifier' => (string) $this->image->imageIdentifier(),
            'requesterName' => $hideRequest->requesterName(),
            'requesterEmail' => $hideRequest->requesterEmail(),
            'reason' => $hideRequest->reason(),
            'status' => $hideRequest->status()->value,
        ];
    }
}
