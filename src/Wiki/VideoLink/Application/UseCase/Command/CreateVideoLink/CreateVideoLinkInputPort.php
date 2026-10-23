<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Domain\ValueObject\DisplayOrder;
use Source\Wiki\VideoLink\Domain\ValueObject\DurationSeconds;
use Source\Wiki\VideoLink\Domain\ValueObject\ThumbnailUrl;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoTitle;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface CreateVideoLinkInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function resourceType(): ResourceType;

    public function wikiIdentifier(): WikiIdentifier;

    public function url(): ExternalContentLink;

    public function videoUsage(): VideoUsage;

    public function title(): VideoTitle;

    public function displayOrder(): DisplayOrder;

    public function durationSeconds(): DurationSeconds;

    public function thumbnailUrl(): ?ThumbnailUrl;

    public function publishedAt(): ?DateTimeImmutable;
}
