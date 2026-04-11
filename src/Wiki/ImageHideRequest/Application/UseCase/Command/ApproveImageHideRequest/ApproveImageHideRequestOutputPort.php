<?php

declare(strict_types=1);

namespace Source\Wiki\ImageHideRequest\Application\UseCase\Command\ApproveImageHideRequest;

use Source\Wiki\ImageHideRequest\Domain\Entity\ImageHideRequest;

interface ApproveImageHideRequestOutputPort
{
    public function setImageHideRequest(ImageHideRequest $imageHideRequest): void;

    /**
     * @return array{requestIdentifier: ?string, imageIdentifier: ?string, status: ?string, reviewerComment: ?string}
     */
    public function toArray(): array;
}
