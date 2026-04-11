<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RejectImage;

use Source\Wiki\Image\Domain\Entity\DraftImage;

interface RejectImageOutputPort
{
    public function setDraftImage(DraftImage $draftImage): void;

    /**
     * @return array{imageIdentifier: ?string, resourceType: ?string, imageUsage: ?string, status: ?string}
     */
    public function toArray(): array;
}
