<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RequestImageDeletion;

use Source\Wiki\Image\Domain\Entity\Image;

interface RequestImageDeletionOutputPort
{
    public function setImage(Image $image): void;

    /**
     * @return array{imageIdentifier: ?string, requesterName: ?string, requesterEmail: ?string, reason: ?string, isHidden: ?bool}
     */
    public function toArray(): array;
}
