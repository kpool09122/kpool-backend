<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Application\UseCase\Query\AgencyWikiBasicReadModel;

class AgencyWikiBasicReadModelTest extends TestCase
{
    public function testSerializesValues(): void
    {
        $subject = new AgencyWikiBasicReadModel('name-value', 'normalizedName-value', 'ceo-value', 'normalizedCeo-value', 'foundedIn-value', 'parentAgencyIdentifier-value', 'status-value', 'officialWebsite-value', ['https://example.com']);
        $this->assertSame([
            'name' => 'name-value',
            'normalizedName' => 'normalizedName-value',
            'ceo' => 'ceo-value',
            'normalizedCeo' => 'normalizedCeo-value',
            'foundedIn' => 'foundedIn-value',
            'parentAgencyIdentifier' => 'parentAgencyIdentifier-value',
            'status' => 'status-value',
            'officialWebsite' => 'officialWebsite-value',
            'socialLinks' => ['https://example.com'],
        ], $subject->toArray());
    }

    public function testPreservesNullValues(): void
    {
        $subject = new AgencyWikiBasicReadModel('name-value', 'normalizedName-value', 'ceo-value', 'normalizedCeo-value', null, null, null, null, ['https://example.com']);
        $this->assertSame([
            'name' => 'name-value',
            'normalizedName' => 'normalizedName-value',
            'ceo' => 'ceo-value',
            'normalizedCeo' => 'normalizedCeo-value',
            'foundedIn' => null,
            'parentAgencyIdentifier' => null,
            'status' => null,
            'officialWebsite' => null,
            'socialLinks' => ['https://example.com'],
        ], $subject->toArray());
    }
}
