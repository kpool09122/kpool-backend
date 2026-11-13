<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\UpdateImageMetadata;

use InvalidArgumentException;
use Source\Wiki\Image\Domain\ValueObject\ImageAuthor;
use Source\Wiki\Image\Domain\ValueObject\ImageLicense;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class UpdateImageMetadataInput implements UpdateImageMetadataInputPort
{
    public function __construct(
        private ImageIdentifier $imageIdentifier,
        private PrincipalIdentifier $principalIdentifier,
        private string $sourceName,
        private ImageAuthor $author,
        private ImageLicense $license,
        private string $altText,
        private int $displayOrder,
    ) {
        if (trim($sourceName) === '' || mb_strlen($sourceName) > 255) {
            throw new InvalidArgumentException('Source name must contain between 1 and 255 characters.');
        }
        if (mb_strlen($altText) > 512) {
            throw new InvalidArgumentException('Alternative text must not exceed 512 characters.');
        }
        if ($displayOrder < 0 || $displayOrder > 2147483647) {
            throw new InvalidArgumentException('Display order must be between 0 and 2147483647.');
        }
    }

    public function imageIdentifier(): ImageIdentifier
    {
        return $this->imageIdentifier;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function sourceName(): string
    {
        return $this->sourceName;
    }

    public function author(): ImageAuthor
    {
        return $this->author;
    }

    public function license(): ImageLicense
    {
        return $this->license;
    }

    public function altText(): string
    {
        return $this->altText;
    }

    public function displayOrder(): int
    {
        return $this->displayOrder;
    }
}
