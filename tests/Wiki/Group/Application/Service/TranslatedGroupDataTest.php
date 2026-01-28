<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\Service;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Group\Application\Service\TranslatedGroupData;

class TranslatedGroupDataTest extends TestCase
{
    public function testConstructorSetsValuesCorrectly(): void
    {
        $translatedName = 'TWICE';
        $translatedDescription = '### TWICE';

        $data = new TranslatedGroupData(
            translatedName: $translatedName,
            translatedDescription: $translatedDescription,
        );

        $this->assertSame($translatedName, $data->translatedName());
        $this->assertSame($translatedDescription, $data->translatedDescription());
    }

    public function testGettersReturnCorrectValues(): void
    {
        $data = new TranslatedGroupData(
            translatedName: 'NewJeans',
            translatedDescription: '### NewJeans (뉴진스)',
        );

        $this->assertSame('NewJeans', $data->translatedName());
        $this->assertSame('### NewJeans (뉴진스)', $data->translatedDescription());
    }

    public function testEmptyValuesAreAllowed(): void
    {
        $data = new TranslatedGroupData(
            translatedName: '',
            translatedDescription: '',
        );

        $this->assertSame('', $data->translatedName());
        $this->assertSame('', $data->translatedDescription());
    }
}
