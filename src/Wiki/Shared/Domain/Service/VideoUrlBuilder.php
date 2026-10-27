<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\Service;

use Source\Wiki\Shared\Domain\ValueObject\TikTokVideoUrl;
use Source\Wiki\Shared\Domain\ValueObject\YouTubeVideoUrl;

final class VideoUrlBuilder implements VideoUrlBuilderInterface
{
    public function build(string $url): YouTubeVideoUrl|TikTokVideoUrl
    {
        return TikTokVideoUrl::hasSupportedHost($url) ? new TikTokVideoUrl($url) : new YouTubeVideoUrl($url);
    }
}
