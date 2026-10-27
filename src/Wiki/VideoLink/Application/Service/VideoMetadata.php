<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\Service;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Shared\Domain\ValueObject\DurationSeconds;
use Source\Wiki\Shared\Domain\ValueObject\TikTokVideoUrl;
use Source\Wiki\Shared\Domain\ValueObject\YouTubeVideoUrl;

final readonly class VideoMetadata
{
    public function __construct(
        public YouTubeVideoUrl|TikTokVideoUrl $url,
        public DurationSeconds $durationSeconds,
        public ImagePath $thumbnailPath,
    ) {
        if ($url->externalVideoId() === null) {
            throw new InvalidArgumentException('A resolved video URL is required.');
        }
    }
}
