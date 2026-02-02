<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Basic\Song;

enum SongType: string
{
    case TITLE_TRACK = 'title_track';
    case B_SIDE = 'b_side';
    case OST = 'ost';
    case SOLO = 'solo';
    case COLLABORATION = 'collaboration';
    case PRE_RELEASE = 'pre_release';
}
