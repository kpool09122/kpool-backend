<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\UnhideImage;

use Source\Wiki\Image\Domain\Entity\Image;

class UnhideImageOutput implements UnhideImageOutputPort
{
    private ?Image $image = null;

    public function setImage(Image $image): void
    {
        $this->image = $image;
    }

    /**
     * @return array{imageIdentifier: ?string, resourceType: ?string, imageUsage: ?string, isHidden: ?bool}
     */
    public function toArray(): array
    {
        if ($this->image === null) {
            return [
                'imageIdentifier' => null,
                'resourceType' => null,
                'imageUsage' => null,
                'isHidden' => null,
            ];
        }

        return [
            'imageIdentifier' => (string) $this->image->imageIdentifier(),
            'resourceType' => $this->image->resourceType()->value,
            'imageUsage' => $this->image->imageUsage()->value,
            'isHidden' => $this->image->isHidden(),
        ];
    }
}
