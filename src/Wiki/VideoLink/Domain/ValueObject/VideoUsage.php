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
}
