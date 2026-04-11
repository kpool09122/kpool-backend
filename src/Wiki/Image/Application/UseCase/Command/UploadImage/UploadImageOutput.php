<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\UploadImage;

use Source\Wiki\Image\Domain\Entity\DraftImage;

class UploadImageOutput implements UploadImageOutputPort
{
    private ?DraftImage $draftImage = null;

    public function setDraftImage(DraftImage $draftImage): void
    {
        $this->draftImage = $draftImage;
    }

    /**
     * @return array{imageIdentifier: ?string, resourceType: ?string, imageUsage: ?string, status: ?string}
     */
    public function toArray(): array
    {
        if ($this->draftImage === null) {
            return [
                'imageIdentifier' => null,
                'resourceType' => null,
                'imageUsage' => null,
                'status' => null,
            ];
        }

        return [
            'imageIdentifier' => (string) $this->draftImage->imageIdentifier(),
            'resourceType' => $this->draftImage->resourceType()->value,
            'imageUsage' => $this->draftImage->imageUsage()->value,
            'status' => $this->draftImage->status()->value,
        ];
    }
}
