<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\ValueObject;

enum ImageLicense: string
{
    case CC_BY = 'CC-BY-4.0';
    case CC_BY_SA = 'CC-BY-SA-4.0';
    case CC_BY_ND = 'CC-BY-ND-4.0';
    case CC_BY_NC = 'CC-BY-NC-4.0';
    case CC_BY_NC_SA = 'CC-BY-NC-SA-4.0';
    case CC_BY_NC_ND = 'CC-BY-NC-ND-4.0';
    case CC0 = 'CC0-1.0';
}
