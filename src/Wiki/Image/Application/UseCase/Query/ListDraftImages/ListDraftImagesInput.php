<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\ListDraftImages;

use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class ListDraftImagesInput implements ListDraftImagesInputPort
{
    public function __construct(
        private ApprovalStatus $status,
        private ?WikiIdentifier $wikiIdentifier = null,
        private ?int $perPage = null,
    ) {
    }

    public function perPage(): int
    {
        return $this->perPage ?? 10;
    }

    public function wikiIdentifier(): ?WikiIdentifier
    {
        return $this->wikiIdentifier;
    }

    public function status(): ApprovalStatus
    {
        return $this->status;
    }
}
