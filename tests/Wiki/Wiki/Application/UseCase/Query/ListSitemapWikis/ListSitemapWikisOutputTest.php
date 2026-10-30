<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\ListSitemapWikis;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Application\UseCase\Query\ListSitemapWikis\ListSitemapWikisOutput;
use Source\Wiki\Wiki\Application\UseCase\Query\SitemapWikiReadModel;

class ListSitemapWikisOutputTest extends TestCase
{
    public function testEmptyOutputAndExplicitTermination(): void
    {
        $output = new ListSitemapWikisOutput();
        $this->assertSame(['wikis' => [], 'nextCursor' => null], $output->toArray());
        $output->output([], null);
        $this->assertSame(['wikis' => [], 'nextCursor' => null], $output->toArray());
    }

    public function testSerializationAndReplacement(): void
    {
        $output = new ListSitemapWikisOutput();
        $wiki = new SitemapWikiReadModel('gr-test', 'ja', 'group', null);
        $cursor = '01965bb2-bcc9-7c6f-8b90-89f7f217f101';
        $output->output([$wiki], $cursor);
        $this->assertSame([
            'wikis' => [['slug' => 'gr-test', 'language' => 'ja', 'resourceType' => 'group', 'lastModifiedAt' => null]],
            'nextCursor' => $cursor,
        ], $output->toArray());
        $output->output([], null);
        $this->assertSame(['wikis' => [], 'nextCursor' => null], $output->toArray());
    }
}
