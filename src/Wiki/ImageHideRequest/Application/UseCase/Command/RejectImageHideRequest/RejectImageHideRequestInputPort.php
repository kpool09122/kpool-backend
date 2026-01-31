<?php

declare(strict_types=1);

namespace Source\Wiki\ImageHideRequest\Application\UseCase\Command\RejectImageHideRequest;

use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface RejectImageHideRequestInputPort
{
    public function requestIdentifier(): ImageHideRequestIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;

    public function reviewerComment(): string;
}
