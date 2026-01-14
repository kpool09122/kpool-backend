<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\ValueObject;

enum ImageUsage: string
{
    case PROFILE = 'profile';
    case COVER = 'cover';
    case LOGO = 'logo';
    case ADDITIONAL = 'additional';
}
