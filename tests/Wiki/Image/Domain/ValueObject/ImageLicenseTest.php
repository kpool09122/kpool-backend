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
            'CC0-1.0',
        ], array_column(ImageLicense::cases(), 'value'));
    }
}
