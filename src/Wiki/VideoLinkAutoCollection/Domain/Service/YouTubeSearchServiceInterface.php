<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLinkAutoCollection\Domain\Service;

use Source\Wiki\VideoLinkAutoCollection\Domain\Exception\VideoSearchFailedException;
use Source\Wiki\VideoLinkAutoCollection\Domain\ValueObject\YouTubeVideoInfo;

interface YouTubeSearchServiceInterface
{
    /**
     * @return YouTubeVideoInfo[]
     * @throws VideoSearchFailedException
     */
    public function searchVideos(string $keyword): array;
}
