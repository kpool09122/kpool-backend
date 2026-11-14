<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Image\Domain\ValueObject\ImageLicense;

class ImageLicenseTest extends TestCase
{
    public function testSupportedLicenseIdentifiers(): void
    {
        $this->assertSame([
            'CC-BY-4.0',
            'CC-BY-SA-4.0',
            'CC-BY-ND-4.0',
            'CC-BY-NC-4.0',
            'CC-BY-NC-SA-4.0',
            'CC-BY-NC-ND-4.0',
            'CC-BY-3.0',
            'CC-BY-SA-3.0',
            'CC-BY-ND-3.0',
            'CC-BY-NC-3.0',
            'CC-BY-NC-SA-3.0',
            'CC-BY-NC-ND-3.0',
            'CC-BY-2.5',
            'CC-BY-SA-2.5',
            'CC-BY-ND-2.5',
            'CC-BY-NC-2.5',
            'CC-BY-NC-SA-2.5',
            'CC-BY-NC-ND-2.5',
            'CC-BY-2.0',
            'CC-BY-SA-2.0',
            'CC-BY-ND-2.0',
            'CC-BY-NC-2.0',
            'CC-BY-NC-SA-2.0',
            'CC-BY-NC-ND-2.0',
            'CC-BY-1.0',
            'CC-BY-SA-1.0',
            'CC-BY-ND-1.0',
            'CC-BY-NC-1.0',
            'CC-BY-NC-SA-1.0',
            'CC-BY-ND-NC-1.0',
            'CC0-1.0',
            'PUBLIC-DOMAIN',
        ], array_column(ImageLicense::cases(), 'value'));
    }
}
