<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Application\Service;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Agency\Application\Service\TranslatedAgencyData;

class TranslatedAgencyDataTest extends TestCase
{
    public function testConstructorSetsValuesCorrectly(): void
    {
        $translatedName = 'JYP Entertainment';
        $translatedCEO = 'J.Y. Park';
        $translatedDescription = '### JYP Entertainment';

        $data = new TranslatedAgencyData(
            translatedName: $translatedName,
            translatedCEO: $translatedCEO,
            translatedDescription: $translatedDescription,
        );

        $this->assertSame($translatedName, $data->translatedName());
        $this->assertSame($translatedCEO, $data->translatedCEO());
        $this->assertSame($translatedDescription, $data->translatedDescription());
    }

    public function testGettersReturnCorrectValues(): void
    {
        $data = new TranslatedAgencyData(
            translatedName: 'SM Entertainment',
            translatedCEO: 'Lee Soo-man',
            translatedDescription: '### SM Entertainment (SM엔터테인먼트)',
        );

        $this->assertSame('SM Entertainment', $data->translatedName());
        $this->assertSame('Lee Soo-man', $data->translatedCEO());
        $this->assertSame('### SM Entertainment (SM엔터테인먼트)', $data->translatedDescription());
    }

    public function testEmptyValuesAreAllowed(): void
    {
        $data = new TranslatedAgencyData(
            translatedName: '',
            translatedCEO: '',
            translatedDescription: '',
        );

        $this->assertSame('', $data->translatedName());
        $this->assertSame('', $data->translatedCEO());
        $this->assertSame('', $data->translatedDescription());
    }
}
