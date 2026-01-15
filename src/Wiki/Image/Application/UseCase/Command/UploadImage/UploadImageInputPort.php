<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\UploadImage;

use DateTimeImmutable;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface UploadImageInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function publishedImageIdentifier(): ?ImageIdentifier;

    public function resourceType(): ResourceType;

    public function draftResourceIdentifier(): ResourceIdentifier;

    public function base64EncodedImage(): string;

    public function imageUsage(): ImageUsage;

    public function displayOrder(): int;

    public function sourceUrl(): string;

    public function sourceName(): string;

    public function altText(): string;

    public function agreedToTermsAt(): DateTimeImmutable;
}
