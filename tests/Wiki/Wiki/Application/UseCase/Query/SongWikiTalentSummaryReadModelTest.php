<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Application\UseCase\Query\SongWikiTalentSummaryReadModel;

class SongWikiTalentSummaryReadModelTest extends TestCase
{
    public function testSerializesValues(): void
    {
        $subject = new SongWikiTalentSummaryReadModel('wikiIdentifier-value', 'slug-value', 'language-value', 'name-value', 'normalizedName-value', 'realName-value', 'normalizedRealName-value', 'birthday-value', 'agencyIdentifier-value', 'emoji-value', 'representativeSymbol-value', 'position-value', 'mbti-value', 'zodiacSign-value', 'englishLevel-value', 7, 'bloodType-value', 'fandomName-value');
        $this->assertSame([
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
        ], $subject->toArray());
    }

    public function testPreservesNullValues(): void
    {
        $subject = new SongWikiTalentSummaryReadModel('wikiIdentifier-value', 'slug-value', 'language-value', 'name-value', 'normalizedName-value', 'realName-value', 'normalizedRealName-value', null, null, 'emoji-value', 'representativeSymbol-value', 'position-value', null, null, null, null, null, 'fandomName-value');
        $this->assertSame([
            'wikiIdentifier' => 'wikiIdentifier-value',
            'slug' => 'slug-value',
            'language' => 'language-value',
            'name' => 'name-value',
            'normalizedName' => 'normalizedName-value',
            'realName' => 'realName-value',
            'normalizedRealName' => 'normalizedRealName-value',
            'birthday' => null,
            'agencyIdentifier' => null,
            'emoji' => 'emoji-value',
            'representativeSymbol' => 'representativeSymbol-value',
            'position' => 'position-value',
            'mbti' => null,
            'zodiacSign' => null,
            'englishLevel' => null,
            'height' => null,
            'bloodType' => null,
            'fandomName' => 'fandomName-value',
        ], $subject->toArray());
    }
}
