<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Application\UseCase\Query\GroupWikiBasicReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiAgencySummaryReadModel;

class GroupWikiBasicReadModelTest extends TestCase
{
    public function testSerializesValues(): void
    {
        $subject = new GroupWikiBasicReadModel('name-value', 'normalizedName-value', 'agencyIdentifier-value', new WikiAgencySummaryReadModel('wikiIdentifier-value', 'slug-value', 'language-value', 'name-value', 'normalizedName-value'), 'groupType-value', 'status-value', 'generation-value', 'debutDate-value', 'disbandDate-value', 'fandomName-value', [['colorCode' => '#ffffff', 'label' => 'White']], 'emoji-value', 'representativeSymbol-value');
        $this->assertSame([
            'name' => 'name-value',
            'normalizedName' => 'normalizedName-value',
            'agencyIdentifier' => 'agencyIdentifier-value',
            'agency' => [
            'wikiIdentifier' => 'wikiIdentifier-value',
            'slug' => 'slug-value',
            'language' => 'language-value',
            'name' => 'name-value',
            'normalizedName' => 'normalizedName-value',
        ],
            'groupType' => 'groupType-value',
            'status' => 'status-value',
            'generation' => 'generation-value',
            'debutDate' => 'debutDate-value',
            'disbandDate' => 'disbandDate-value',
            'fandomName' => 'fandomName-value',
            'officialColors' => [['colorCode' => '#ffffff', 'label' => 'White']],
            'emoji' => 'emoji-value',
            'representativeSymbol' => 'representativeSymbol-value',
        ], $subject->toArray());
    }

    public function testPreservesNullValues(): void
    {
        $subject = new GroupWikiBasicReadModel('name-value', 'normalizedName-value', null, null, null, null, null, null, null, 'fandomName-value', [['colorCode' => '#ffffff', 'label' => 'White']], 'emoji-value', 'representativeSymbol-value');
        $this->assertSame([
            'name' => 'name-value',
            'normalizedName' => 'normalizedName-value',
            'agencyIdentifier' => null,
            'agency' => null,
            'groupType' => null,
            'status' => null,
            'generation' => null,
            'debutDate' => null,
            'disbandDate' => null,
            'fandomName' => 'fandomName-value',
            'officialColors' => [['colorCode' => '#ffffff', 'label' => 'White']],
            'emoji' => 'emoji-value',
            'representativeSymbol' => 'representativeSymbol-value',
        ], $subject->toArray());
    }
}
