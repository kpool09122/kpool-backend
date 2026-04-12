<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RequestImageHide;

use Source\Wiki\Image\Domain\Entity\Image;

interface RequestImageHideOutputPort
{
    public function setImage(Image $image): void;

    /**
     * @return array{imageIdentifier: ?string, requesterName: ?string, requesterEmail: ?string, reason: ?string, status: ?string}
     */
    public function toArray(): array;
}
