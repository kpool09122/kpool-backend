<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\ListDraftImages;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

interface ListDraftImagesInputPort
{
    public function perPage(): int;

    public function translationSetIdentifier(): ?TranslationSetIdentifier;

    public function status(): ApprovalStatus;
}
