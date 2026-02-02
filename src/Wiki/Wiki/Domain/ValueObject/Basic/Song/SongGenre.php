<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Basic\Song;

enum SongGenre: string
{
    case POP = 'pop';
    case DANCE = 'dance';
    case BALLAD = 'ballad';
    case RNB = 'rnb';
    case HIPHOP = 'hiphop';
    case EDM = 'edm';
    case ROCK = 'rock';
    case JAZZ = 'jazz';
    case ACOUSTIC = 'acoustic';
}
