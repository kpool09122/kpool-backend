<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiAgencySummaryReadModel;

class WikiAgencySummaryReadModelTest extends TestCase
{
    public function testSerializesValues(): void
    {
        $subject = new WikiAgencySummaryReadModel('wikiIdentifier-value', 'slug-value', 'language-value', 'name-value', 'normalizedName-value');
        $this->assertSame([
            'wikiIdentifier' => 'wikiIdentifier-value',
            'slug' => 'slug-value',
            'language' => 'language-value',
            'name' => 'name-value',
            'normalizedName' => 'normalizedName-value',
        ], $subject->toArray());
    }

    public function testPreservesNullValues(): void
    {
        $subject = new WikiAgencySummaryReadModel('wikiIdentifier-value', 'slug-value', 'language-value', 'name-value', 'normalizedName-value');
        $this->assertSame([
            'wikiIdentifier' => 'wikiIdentifier-value',
            'slug' => 'slug-value',
            'language' => 'language-value',
            'name' => 'name-value',
            'normalizedName' => 'normalizedName-value',
        ], $subject->toArray());
    }
}
