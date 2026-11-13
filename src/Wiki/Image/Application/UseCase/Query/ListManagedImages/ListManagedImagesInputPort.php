<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Query\ListManagedImages;

use Source\Wiki\Image\Domain\ValueObject\ImageLicense;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface ListManagedImagesInputPort
{
    public function licenseMissing(): bool;

    public function license(): ?ImageLicense;

    public function authorMissing(): bool;

    public function principalIdentifier(): PrincipalIdentifier;

    public function perPage(): int;

    public function status(): ApprovalStatus;
}
