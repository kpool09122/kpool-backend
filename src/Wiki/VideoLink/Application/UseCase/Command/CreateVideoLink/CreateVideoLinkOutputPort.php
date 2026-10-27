<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink;

use Source\Shared\Domain\ValueObject\ImagePath;

interface CreateVideoLinkOutputPort
{
    public function output(ImagePath $thumbnailPath): void;
}
