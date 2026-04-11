<?php

declare(strict_types=1);

namespace Source\Wiki\ImageHideRequest\Application\UseCase\Command\RejectImageHideRequest;

use Source\Wiki\ImageHideRequest\Domain\Entity\ImageHideRequest;

interface RejectImageHideRequestOutputPort
{
    public function setImageHideRequest(ImageHideRequest $imageHideRequest): void;

    /**
     * @return array{requestIdentifier: ?string, imageIdentifier: ?string, status: ?string, reviewerComment: ?string}
     */
    public function toArray(): array;
}
