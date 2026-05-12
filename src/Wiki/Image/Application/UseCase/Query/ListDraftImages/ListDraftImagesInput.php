<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\ListDraftImages;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

readonly class ListDraftImagesInput implements ListDraftImagesInputPort
{
    public function __construct(
        private ApprovalStatus $status,
        private ?TranslationSetIdentifier $translationSetIdentifier = null,
        private ?int $perPage = null,
    ) {
    }

    public function perPage(): int
    {
        return $this->perPage ?? 10;
    }

    public function translationSetIdentifier(): ?TranslationSetIdentifier
    {
        return $this->translationSetIdentifier;
    }

    public function status(): ApprovalStatus
    {
        return $this->status;
    }
}
