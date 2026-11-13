<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\ListManagedImages;

use Source\Wiki\Image\Domain\ValueObject\ImageLicense;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class ListManagedImagesInput implements ListManagedImagesInputPort
{
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private ApprovalStatus $status,
        private ?int $perPage = null,
        private ?ImageLicense $license = null,
        private bool $authorMissing = false,
        private bool $licenseMissing = false,
    ) {
    }

    public function licenseMissing(): bool
    {
        return $this->licenseMissing;
    }

    public function license(): ?ImageLicense
    {
        return $this->license;
    }

    public function authorMissing(): bool
    {
        return $this->authorMissing;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function perPage(): int
    {
        return $this->perPage ?? 10;
    }

    public function status(): ApprovalStatus
    {
        return $this->status;
    }
}
