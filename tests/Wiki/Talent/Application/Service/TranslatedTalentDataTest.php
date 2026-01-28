<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\Service;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Talent\Application\Service\TranslatedTalentData;

class TranslatedTalentDataTest extends TestCase
{
    public function testConstructorSetsValuesCorrectly(): void
    {
        $translatedName = 'Chaeyoung';
        $translatedRealName = 'Son Chaeyoung';
        $translatedCareer = '### Chaeyoung is a member of TWICE.';

        $data = new TranslatedTalentData(
            translatedName: $translatedName,
            translatedRealName: $translatedRealName,
            translatedCareer: $translatedCareer,
        );

        $this->assertSame($translatedName, $data->translatedName());
        $this->assertSame($translatedRealName, $data->translatedRealName());
        $this->assertSame($translatedCareer, $data->translatedCareer());
    }

    public function testGettersReturnCorrectValues(): void
    {
        $data = new TranslatedTalentData(
            translatedName: 'Momo',
            translatedRealName: 'Hirai Momo',
            translatedCareer: '### Momo is a Japanese member of TWICE.',
        );

        $this->assertSame('Momo', $data->translatedName());
        $this->assertSame('Hirai Momo', $data->translatedRealName());
        $this->assertSame('### Momo is a Japanese member of TWICE.', $data->translatedCareer());
    }

    public function testEmptyValuesAreAllowed(): void
    {
        $data = new TranslatedTalentData(
            translatedName: '',
            translatedRealName: '',
            translatedCareer: '',
        );

        $this->assertSame('', $data->translatedName());
        $this->assertSame('', $data->translatedRealName());
        $this->assertSame('', $data->translatedCareer());
    }
}
