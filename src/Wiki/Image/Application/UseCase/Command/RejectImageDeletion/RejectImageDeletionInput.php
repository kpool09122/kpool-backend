<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RejectImageDeletion;

use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class RejectImageDeletionInput implements RejectImageDeletionInputPort
{
    public function __construct(
        private ImageIdentifier $imageIdentifier,
        private PrincipalIdentifier $principalIdentifier,
        private string $rejectReason,
    ) {
    }

    public function imageIdentifier(): ImageIdentifier
    {
        return $this->imageIdentifier;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function rejectReason(): string
    {
        return $this->rejectReason;
    }
}
