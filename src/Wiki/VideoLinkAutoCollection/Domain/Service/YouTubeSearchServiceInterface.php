<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLinkAutoCollection\Domain\Service;

use Source\Wiki\VideoLinkAutoCollection\Domain\ValueObject\YouTubeVideoInfo;

interface YouTubeSearchServiceInterface
{
    /**
     * @return YouTubeVideoInfo[]
     */
    public function searchVideos(string $keyword): array;
}
