<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink;

use Source\Shared\Domain\ValueObject\ImagePath;

class CreateVideoLinkOutput implements CreateVideoLinkOutputPort
{
    private ?ImagePath $thumbnailPath = null;

    public function output(ImagePath $thumbnailPath): void
    {
        $this->thumbnailPath = $thumbnailPath;
    }

    public function thumbnailPath(): ?ImagePath
    {
        return $this->thumbnailPath;
    }
}
