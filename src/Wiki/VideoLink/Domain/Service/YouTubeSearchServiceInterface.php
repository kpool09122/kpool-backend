<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Service;

use Source\Wiki\VideoLink\Domain\Exception\VideoSearchFailedException;
use Source\Wiki\VideoLink\Domain\ValueObject\YouTubeVideoInfo;

interface YouTubeSearchServiceInterface
{
    /**
     * @return YouTubeVideoInfo[]
     * @throws VideoSearchFailedException
     */
    public function searchVideos(string $keyword): array;
}
