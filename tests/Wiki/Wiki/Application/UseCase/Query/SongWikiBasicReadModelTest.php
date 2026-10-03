<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Application\UseCase\Query\SongWikiBasicReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\SongWikiTalentSummaryReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\TalentWikiGroupSummaryReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiAgencySummaryReadModel;

class SongWikiBasicReadModelTest extends TestCase
{
    public function testSerializesValues(): void
    {
        $subject = new SongWikiBasicReadModel('name-value', 'normalizedName-value', 'songType-value', ['pop'], 'agencyIdentifier-value', new WikiAgencySummaryReadModel('wikiIdentifier-value', 'slug-value', 'language-value', 'name-value', 'normalizedName-value'), 'releaseDate-value', 'albumName-value', 'lyricist-value', 'normalizedLyricist-value', 'composer-value', 'normalizedComposer-value', 'arranger-value', 'normalizedArranger-value', [new TalentWikiGroupSummaryReadModel('wikiIdentifier-value', 'slug-value', 'language-value', 'name-value', 'normalizedName-value', 'agencyIdentifier-value', 'groupType-value', 'status-value', 'generation-value', 'debutDate-value', 'disbandDate-value', 'fandomName-value', [['colorCode' => '#ffffff', 'label' => 'White']], 'emoji-value', 'representativeSymbol-value')], [new SongWikiTalentSummaryReadModel('wikiIdentifier-value', 'slug-value', 'language-value', 'name-value', 'normalizedName-value', 'realName-value', 'normalizedRealName-value', 'birthday-value', 'agencyIdentifier-value', 'emoji-value', 'representativeSymbol-value', 'position-value', 'mbti-value', 'zodiacSign-value', 'englishLevel-value', 7, 'bloodType-value', 'fandomName-value')]);
        $this->assertSame([
            'name' => 'name-value',
            'normalizedName' => 'normalizedName-value',
            'songType' => 'songType-value',
            'genres' => ['pop'],
            'agencyIdentifier' => 'agencyIdentifier-value',
            'agency' => [
            'wikiIdentifier' => 'wikiIdentifier-value',
            'slug' => 'slug-value',
            'language' => 'language-value',
            'name' => 'name-value',
            'normalizedName' => 'normalizedName-value',
        ],
            'releaseDate' => 'releaseDate-value',
            'albumName' => 'albumName-value',
            'lyricist' => 'lyricist-value',
            'normalizedLyricist' => 'normalizedLyricist-value',
            'composer' => 'composer-value',
            'normalizedComposer' => 'normalizedComposer-value',
            'arranger' => 'arranger-value',
            'normalizedArranger' => 'normalizedArranger-value',
            'groups' => [[
            'wikiIdentifier' => 'wikiIdentifier-value',
            'slug' => 'slug-value',
            'language' => 'language-value',
            'name' => 'name-value',
            'normalizedName' => 'normalizedName-value',
            'agencyIdentifier' => 'agencyIdentifier-value',
            'groupType' => 'groupType-value',
            'status' => 'status-value',
            'generation' => 'generation-value',
            'debutDate' => 'debutDate-value',
            'disbandDate' => 'disbandDate-value',
            'fandomName' => 'fandomName-value',
            'officialColors' => [['colorCode' => '#ffffff', 'label' => 'White']],
            'emoji' => 'emoji-value',
            'representativeSymbol' => 'representativeSymbol-value',
        ]],
            'talents' => [[
            'wikiIdentifier' => 'wikiIdentifier-value',
            'slug' => 'slug-value',
            'language' => 'language-value',
            'name' => 'name-value',
            'normalizedName' => 'normalizedName-value',
            'realName' => 'realName-value',
            'normalizedRealName' => 'normalizedRealName-value',
            'birthday' => 'birthday-value',
            'agencyIdentifier' => 'agencyIdentifier-value',
            'emoji' => 'emoji-value',
            'representativeSymbol' => 'representativeSymbol-value',
            'position' => 'position-value',
            'mbti' => 'mbti-value',
            'zodiacSign' => 'zodiacSign-value',
            'englishLevel' => 'englishLevel-value',
            'height' => 7,
            'bloodType' => 'bloodType-value',
            'fandomName' => 'fandomName-value',
        ]],
        ], $subject->toArray());
    }

    public function testPreservesNullValues(): void
    {
        $subject = new SongWikiBasicReadModel('name-value', 'normalizedName-value', null, ['pop'], null, null, null, null, 'lyricist-value', 'normalizedLyricist-value', 'composer-value', 'normalizedComposer-value', 'arranger-value', 'normalizedArranger-value', [new TalentWikiGroupSummaryReadModel('wikiIdentifier-value', 'slug-value', 'language-value', 'name-value', 'normalizedName-value', 'agencyIdentifier-value', 'groupType-value', 'status-value', 'generation-value', 'debutDate-value', 'disbandDate-value', 'fandomName-value', [['colorCode' => '#ffffff', 'label' => 'White']], 'emoji-value', 'representativeSymbol-value')], [new SongWikiTalentSummaryReadModel('wikiIdentifier-value', 'slug-value', 'language-value', 'name-value', 'normalizedName-value', 'realName-value', 'normalizedRealName-value', 'birthday-value', 'agencyIdentifier-value', 'emoji-value', 'representativeSymbol-value', 'position-value', 'mbti-value', 'zodiacSign-value', 'englishLevel-value', 7, 'bloodType-value', 'fandomName-value')]);
        $this->assertSame([
            'name' => 'name-value',
            'normalizedName' => 'normalizedName-value',
            'songType' => null,
            'genres' => ['pop'],
            'agencyIdentifier' => null,
            'agency' => null,
            'releaseDate' => null,
            'albumName' => null,
            'lyricist' => 'lyricist-value',
            'normalizedLyricist' => 'normalizedLyricist-value',
            'composer' => 'composer-value',
            'normalizedComposer' => 'normalizedComposer-value',
            'arranger' => 'arranger-value',
            'normalizedArranger' => 'normalizedArranger-value',
            'groups' => [[
            'wikiIdentifier' => 'wikiIdentifier-value',
            'slug' => 'slug-value',
            'language' => 'language-value',
            'name' => 'name-value',
            'normalizedName' => 'normalizedName-value',
            'agencyIdentifier' => 'agencyIdentifier-value',
            'groupType' => 'groupType-value',
            'status' => 'status-value',
            'generation' => 'generation-value',
            'debutDate' => 'debutDate-value',
            'disbandDate' => 'disbandDate-value',
            'fandomName' => 'fandomName-value',
            'officialColors' => [['colorCode' => '#ffffff', 'label' => 'White']],
            'emoji' => 'emoji-value',
            'representativeSymbol' => 'representativeSymbol-value',
        ]],
            'talents' => [[
            'wikiIdentifier' => 'wikiIdentifier-value',
            'slug' => 'slug-value',
            'language' => 'language-value',
            'name' => 'name-value',
            'normalizedName' => 'normalizedName-value',
            'realName' => 'realName-value',
            'normalizedRealName' => 'normalizedRealName-value',
            'birthday' => 'birthday-value',
            'agencyIdentifier' => 'agencyIdentifier-value',
            'emoji' => 'emoji-value',
            'representativeSymbol' => 'representativeSymbol-value',
            'position' => 'position-value',
            'mbti' => 'mbti-value',
            'zodiacSign' => 'zodiacSign-value',
            'englishLevel' => 'englishLevel-value',
            'height' => 7,
            'bloodType' => 'bloodType-value',
            'fandomName' => 'fandomName-value',
        ]],
        ], $subject->toArray());
    }
}
