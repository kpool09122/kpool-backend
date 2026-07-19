<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RequestImageDeletion;

use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;

interface RequestImageDeletionInputPort
{
    public function imageIdentifier(): ImageIdentifier;

    public function requesterName(): string;

    public function requesterEmail(): string;

    public function reason(): string;
}
