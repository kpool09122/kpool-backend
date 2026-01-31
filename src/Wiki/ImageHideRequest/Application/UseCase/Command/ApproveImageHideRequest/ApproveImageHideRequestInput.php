<?php

declare(strict_types=1);

namespace Source\Wiki\ImageHideRequest\Application\UseCase\Command\ApproveImageHideRequest;

use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class ApproveImageHideRequestInput implements ApproveImageHideRequestInputPort
{
    public function __construct(
        private ImageHideRequestIdentifier $requestIdentifier,
        private PrincipalIdentifier $principalIdentifier,
        private string $reviewerComment,
    ) {
    }

    public function requestIdentifier(): ImageHideRequestIdentifier
    {
        return $this->requestIdentifier;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function reviewerComment(): string
    {
        return $this->reviewerComment;
    }
}
