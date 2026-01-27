<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Application\Service;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Song\Application\Service\TranslatedSongData;

class TranslatedSongDataTest extends TestCase
{
    public function testConstructorSetsValuesCorrectly(): void
    {
        $translatedName = 'Feel Special';
        $translatedLyricist = 'J.Y. Park';
        $translatedComposer = 'J.Y. Park';
        $translatedOverview = '### Feel Special';

        $data = new TranslatedSongData(
            translatedName: $translatedName,
            translatedLyricist: $translatedLyricist,
            translatedComposer: $translatedComposer,
            translatedOverview: $translatedOverview,
        );

        $this->assertSame($translatedName, $data->translatedName());
        $this->assertSame($translatedLyricist, $data->translatedLyricist());
        $this->assertSame($translatedComposer, $data->translatedComposer());
        $this->assertSame($translatedOverview, $data->translatedOverview());
    }

    public function testGettersReturnCorrectValues(): void
    {
        $data = new TranslatedSongData(
            translatedName: 'TT',
            translatedLyricist: 'Sam Lewis',
            translatedComposer: 'Black Eyed Pilseung',
            translatedOverview: '### TT is a song by TWICE',
        );

        $this->assertSame('TT', $data->translatedName());
        $this->assertSame('Sam Lewis', $data->translatedLyricist());
        $this->assertSame('Black Eyed Pilseung', $data->translatedComposer());
        $this->assertSame('### TT is a song by TWICE', $data->translatedOverview());
    }

    public function testEmptyValuesAreAllowed(): void
    {
        $data = new TranslatedSongData(
            translatedName: '',
            translatedLyricist: '',
            translatedComposer: '',
            translatedOverview: '',
        );

        $this->assertSame('', $data->translatedName());
        $this->assertSame('', $data->translatedLyricist());
        $this->assertSame('', $data->translatedComposer());
        $this->assertSame('', $data->translatedOverview());
    }
}
