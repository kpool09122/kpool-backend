<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\UpdateImageMetadata;

use Source\Wiki\Image\Domain\ValueObject\ImageAuthor;
use Source\Wiki\Image\Domain\ValueObject\ImageLicense;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface UpdateImageMetadataInputPort
{
    public function imageIdentifier(): ImageIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;

    public function sourceName(): string;

    public function author(): ImageAuthor;

    public function license(): ImageLicense;

    public function altText(): string;

    public function displayOrder(): int;
}
