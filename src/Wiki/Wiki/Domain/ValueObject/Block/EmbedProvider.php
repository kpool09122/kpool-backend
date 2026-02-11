<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Block;

enum EmbedProvider: string
{
    case YOUTUBE = 'youtube';
    case SPOTIFY = 'spotify';
    case X = 'x';
    case TIKTOK = 'tiktok';
}
