<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\ApproveImage;

use Source\Wiki\Image\Domain\Entity\Image;

class ApproveImageOutput implements ApproveImageOutputPort
{
    private ?Image $image = null;

    public function setImage(Image $image): void
    {
        $this->image = $image;
    }

    /**
     * @return array{imageIdentifier: ?string, resourceType: ?string, isHidden: ?bool}
     */
    public function toArray(): array
    {
        if ($this->image === null) {
            return [
                'imageIdentifier' => null,
                'resourceType' => null,
                'isHidden' => null,
            ];
        }

        return [
            'imageIdentifier' => (string) $this->image->imageIdentifier(),
            'resourceType' => $this->image->resourceType()->value,
            'isHidden' => $this->image->isHidden(),
        ];
    }
}
