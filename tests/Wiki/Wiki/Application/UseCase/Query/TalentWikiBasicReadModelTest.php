<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Application\UseCase\Query\TalentWikiBasicReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\TalentWikiGroupSummaryReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiAgencySummaryReadModel;

class TalentWikiBasicReadModelTest extends TestCase
{
    public function testSerializesValues(): void
    {
        $subject = new TalentWikiBasicReadModel('name-value', 'normalizedName-value', 'realName-value', 'normalizedRealName-value', 'birthday-value', 'agencyIdentifier-value', new WikiAgencySummaryReadModel('wikiIdentifier-value', 'slug-value', 'language-value', 'name-value', 'normalizedName-value'), 'emoji-value', 'representativeSymbol-value', 'position-value', 'mbti-value', 'zodiacSign-value', 'englishLevel-value', 7, 'bloodType-value', 'fandomName-value', [new TalentWikiGroupSummaryReadModel('wikiIdentifier-value', 'slug-value', 'language-value', 'name-value', 'normalizedName-value', 'agencyIdentifier-value', 'groupType-value', 'status-value', 'generation-value', 'debutDate-value', 'disbandDate-value', 'fandomName-value', [['colorCode' => '#ffffff', 'label' => 'White']], 'emoji-value', 'representativeSymbol-value')]);
        $this->assertSame([
            'name' => 'name-value',
            'normalizedName' => 'normalizedName-value',
            'realName' => 'realName-value',
            'normalizedRealName' => 'normalizedRealName-value',
            'birthday' => 'birthday-value',
            'agencyIdentifier' => 'agencyIdentifier-value',
            'agency' => [
            'wikiIdentifier' => 'wikiIdentifier-value',
            'slug' => 'slug-value',
            'language' => 'language-value',
            'name' => 'name-value',
            'normalizedName' => 'normalizedName-value',
        ],
            'emoji' => 'emoji-value',
            'representativeSymbol' => 'representativeSymbol-value',
            'position' => 'position-value',
            'mbti' => 'mbti-value',
            'zodiacSign' => 'zodiacSign-value',
            'englishLevel' => 'englishLevel-value',
            'height' => 7,
            'bloodType' => 'bloodType-value',
            'fandomName' => 'fandomName-value',
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
        ], $subject->toArray());
    }

    public function testPreservesNullValues(): void
    {
        $subject = new TalentWikiBasicReadModel('name-value', 'normalizedName-value', 'realName-value', 'normalizedRealName-value', null, null, null, 'emoji-value', 'representativeSymbol-value', 'position-value', null, null, null, null, null, 'fandomName-value', [new TalentWikiGroupSummaryReadModel('wikiIdentifier-value', 'slug-value', 'language-value', 'name-value', 'normalizedName-value', 'agencyIdentifier-value', 'groupType-value', 'status-value', 'generation-value', 'debutDate-value', 'disbandDate-value', 'fandomName-value', [['colorCode' => '#ffffff', 'label' => 'White']], 'emoji-value', 'representativeSymbol-value')]);
        $this->assertSame([
            'name' => 'name-value',
            'normalizedName' => 'normalizedName-value',
            'realName' => 'realName-value',
            'normalizedRealName' => 'normalizedRealName-value',
            'birthday' => null,
            'agencyIdentifier' => null,
            'agency' => null,
            'emoji' => 'emoji-value',
            'representativeSymbol' => 'representativeSymbol-value',
            'position' => 'position-value',
            'mbti' => null,
            'zodiacSign' => null,
            'englishLevel' => null,
            'height' => null,
            'bloodType' => null,
            'fandomName' => 'fandomName-value',
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
        ], $subject->toArray());
    }
}
