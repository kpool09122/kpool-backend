<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\SaveVideoLinks;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;

readonly class VideoLinkData
{
    public function __construct(
        public ExternalContentLink $url,
        public VideoUsage $videoUsage,
        public string $title,
        public int $displayOrder,
        public ?string $thumbnailUrl = null,
        public ?DateTimeImmutable $publishedAt = null,
    ) {
    }
}
