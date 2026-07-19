<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RequestImageDeletion;

use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;

readonly class RequestImageDeletionInput implements RequestImageDeletionInputPort
{
    public function __construct(
        private ImageIdentifier $imageIdentifier,
        private string $requesterName,
        private string $requesterEmail,
        private string $reason,
    ) {
    }

    public function imageIdentifier(): ImageIdentifier
    {
        return $this->imageIdentifier;
    }

    public function requesterName(): string
    {
        return $this->requesterName;
    }

    public function requesterEmail(): string
    {
        return $this->requesterEmail;
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
