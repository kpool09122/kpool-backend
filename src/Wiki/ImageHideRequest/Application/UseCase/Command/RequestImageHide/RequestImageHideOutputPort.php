<?php

declare(strict_types=1);

namespace Source\Wiki\ImageHideRequest\Application\UseCase\Command\RequestImageHide;

use Source\Wiki\ImageHideRequest\Domain\Entity\ImageHideRequest;

interface RequestImageHideOutputPort
{
    public function setImageHideRequest(ImageHideRequest $imageHideRequest): void;

    /**
     * @return array{requestIdentifier: ?string, imageIdentifier: ?string, requesterName: ?string, requesterEmail: ?string, reason: ?string, status: ?string}
     */
    public function toArray(): array;
}
