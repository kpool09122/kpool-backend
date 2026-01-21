<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\ValueObject;

enum VideoUsage: string
{
    case MUSIC_VIDEO = 'music_video';
    case LIVE = 'live';
    case INTERVIEW = 'interview';
    case BEHIND_THE_SCENES = 'behind_the_scenes';
    case COVER = 'cover';
    case COLLABORATION = 'collaboration';
    case SHORT = 'short';
    case OTHER = 'other';
    case YOUTUBE_AUTO_VIEW_COUNT = 'youtube_auto_view_count';
    case YOUTUBE_AUTO_LIKE_COUNT = 'youtube_auto_like_count';
    case YOUTUBE_AUTO_RECENT_POPULAR = 'youtube_auto_recent_popular';

    public function isManual(): bool
    {
        return ! $this->isAutoCollected();
    }

    public function isAutoCollected(): bool
    {
        return in_array($this, [
            self::YOUTUBE_AUTO_VIEW_COUNT,
            self::YOUTUBE_AUTO_LIKE_COUNT,
            self::YOUTUBE_AUTO_RECENT_POPULAR,
        ], true);
    }
}
