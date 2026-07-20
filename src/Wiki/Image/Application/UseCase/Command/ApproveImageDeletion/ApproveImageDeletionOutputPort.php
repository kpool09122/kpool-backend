<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\ApproveImageDeletion;

use Source\Wiki\Image\Domain\Entity\Image;

interface ApproveImageDeletionOutputPort
{
    public function setImage(Image $image): void;

    /**
     * @return array{imageIdentifier: ?string, isHidden: ?bool}
     */
    public function toArray(): array;
}
