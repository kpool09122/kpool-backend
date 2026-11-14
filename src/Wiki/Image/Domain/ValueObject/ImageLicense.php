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
    case CC_BY_3_0 = 'CC-BY-3.0';
    case CC_BY_SA_3_0 = 'CC-BY-SA-3.0';
    case CC_BY_ND_3_0 = 'CC-BY-ND-3.0';
    case CC_BY_NC_3_0 = 'CC-BY-NC-3.0';
    case CC_BY_NC_SA_3_0 = 'CC-BY-NC-SA-3.0';
    case CC_BY_NC_ND_3_0 = 'CC-BY-NC-ND-3.0';
    case CC_BY_2_5 = 'CC-BY-2.5';
    case CC_BY_SA_2_5 = 'CC-BY-SA-2.5';
    case CC_BY_ND_2_5 = 'CC-BY-ND-2.5';
    case CC_BY_NC_2_5 = 'CC-BY-NC-2.5';
    case CC_BY_NC_SA_2_5 = 'CC-BY-NC-SA-2.5';
    case CC_BY_NC_ND_2_5 = 'CC-BY-NC-ND-2.5';
    case CC_BY_2_0 = 'CC-BY-2.0';
    case CC_BY_SA_2_0 = 'CC-BY-SA-2.0';
    case CC_BY_ND_2_0 = 'CC-BY-ND-2.0';
    case CC_BY_NC_2_0 = 'CC-BY-NC-2.0';
    case CC_BY_NC_SA_2_0 = 'CC-BY-NC-SA-2.0';
    case CC_BY_NC_ND_2_0 = 'CC-BY-NC-ND-2.0';
    case CC_BY_1_0 = 'CC-BY-1.0';
    case CC_BY_SA_1_0 = 'CC-BY-SA-1.0';
    case CC_BY_ND_1_0 = 'CC-BY-ND-1.0';
    case CC_BY_NC_1_0 = 'CC-BY-NC-1.0';
    case CC_BY_NC_SA_1_0 = 'CC-BY-NC-SA-1.0';
    case CC_BY_ND_NC_1_0 = 'CC-BY-ND-NC-1.0';
    case CC0 = 'CC0-1.0';
    case PUBLIC_DOMAIN = 'PUBLIC-DOMAIN';
}
