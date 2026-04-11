<?php

declare(strict_types=1);

namespace Source\Wiki\ImageHideRequest\Application\UseCase\Command\RequestImageHide;

use Source\Wiki\ImageHideRequest\Domain\Entity\ImageHideRequest;

class RequestImageHideOutput implements RequestImageHideOutputPort
{
    private ?ImageHideRequest $imageHideRequest = null;

    public function setImageHideRequest(ImageHideRequest $imageHideRequest): void
    {
        $this->imageHideRequest = $imageHideRequest;
    }

    /**
     * @return array{requestIdentifier: ?string, imageIdentifier: ?string, requesterName: ?string, requesterEmail: ?string, reason: ?string, status: ?string}
     */
    public function toArray(): array
    {
        if ($this->imageHideRequest === null) {
            return [
                'requestIdentifier' => null,
                'imageIdentifier' => null,
                'requesterName' => null,
                'requesterEmail' => null,
                'reason' => null,
                'status' => null,
            ];
        }

        return [
            'requestIdentifier' => (string) $this->imageHideRequest->requestIdentifier(),
            'imageIdentifier' => (string) $this->imageHideRequest->imageIdentifier(),
            'requesterName' => $this->imageHideRequest->requesterName(),
            'requesterEmail' => $this->imageHideRequest->requesterEmail(),
            'reason' => $this->imageHideRequest->reason(),
            'status' => $this->imageHideRequest->status()->value,
        ];
    }
}
