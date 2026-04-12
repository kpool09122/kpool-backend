<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RejectImageHideRequest;

use Source\Wiki\Image\Domain\Entity\Image;

interface RejectImageHideRequestOutputPort
{
    public function setImage(Image $image): void;

    /**
     * @return array{imageIdentifier: ?string, status: ?string, reviewerComment: ?string, isHidden: ?bool}
     */
    public function toArray(): array;
}
