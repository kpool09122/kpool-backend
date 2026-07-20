<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RejectImageDeletion;

use Source\Wiki\Image\Domain\Entity\Image;

interface RejectImageDeletionOutputPort
{
    public function setImage(Image $image): void;

    /**
     * @return array{imageIdentifier: ?string, rejectReason: ?string, isHidden: ?bool}
     */
    public function toArray(): array;
}
