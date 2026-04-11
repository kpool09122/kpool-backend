<?php

declare(strict_types=1);

namespace Source\Wiki\ImageHideRequest\Application\UseCase\Command\RejectImageHideRequest;

use Source\Wiki\ImageHideRequest\Domain\Entity\ImageHideRequest;

class RejectImageHideRequestOutput implements RejectImageHideRequestOutputPort
{
    private ?ImageHideRequest $imageHideRequest = null;

    public function setImageHideRequest(ImageHideRequest $imageHideRequest): void
    {
        $this->imageHideRequest = $imageHideRequest;
    }

    /**
     * @return array{requestIdentifier: ?string, imageIdentifier: ?string, status: ?string, reviewerComment: ?string}
     */
    public function toArray(): array
    {
        if ($this->imageHideRequest === null) {
            return [
                'requestIdentifier' => null,
                'imageIdentifier' => null,
                'status' => null,
                'reviewerComment' => null,
            ];
        }

        return [
            'requestIdentifier' => (string) $this->imageHideRequest->requestIdentifier(),
            'imageIdentifier' => (string) $this->imageHideRequest->imageIdentifier(),
            'status' => $this->imageHideRequest->status()->value,
            'reviewerComment' => $this->imageHideRequest->reviewerComment(),
        ];
    }
}
