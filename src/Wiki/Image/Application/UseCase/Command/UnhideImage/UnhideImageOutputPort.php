<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\UnhideImage;

use Source\Wiki\Image\Domain\Entity\Image;

interface UnhideImageOutputPort
{
    public function setImage(Image $image): void;

    /**
     * @return array{imageIdentifier: ?string, resourceType: ?string, imageUsage: ?string, isHidden: ?bool}
     */
    public function toArray(): array;
}
