<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Application\UseCase\Query\SitemapWikiReadModel;

class SitemapWikiReadModelTest extends TestCase
{
    public function testSerializationContainsOnlySitemapFields(): void
    {
        $wiki = new SitemapWikiReadModel('sg-test', 'ko', 'song', '2026-10-01T12:34:56+00:00');
        $this->assertSame([
            'slug' => 'sg-test',
            'language' => 'ko',
            'resourceType' => 'song',
            'lastModifiedAt' => '2026-10-01T12:34:56+00:00',
        ], $wiki->toArray());
    }

    public function testUnknownPublicationTimeRemainsNull(): void
    {
        $wiki = new SitemapWikiReadModel('ag-test', 'en', 'agency', null);
        $this->assertNull($wiki->toArray()['lastModifiedAt']);
    }
}
