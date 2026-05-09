<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\ListDraftImages;

use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface ListDraftImagesInputPort
{
    public function perPage(): int;

    public function wikiIdentifier(): ?WikiIdentifier;

    public function status(): ApprovalStatus;
}
