<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\SearchMasterWikis;

use Source\Wiki\Wiki\Application\UseCase\Query\SearchMasterWikis\SearchMasterWikisOutput;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiMasterSearchItemReadModel;
use Tests\TestCase;

class SearchMasterWikisOutputTest extends TestCase
{
    public function testToArrayReturnsWikis(): void
    {
        $item = new WikiMasterSearchItemReadModel(
            id: '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            name: 'Minji',
            slug: 'tl-minji',
            resourceType: 'talent',
        );

        $output = new SearchMasterWikisOutput();
        $output->output([$item]);

        $this->assertSame([
            'wikis' => [$item->toArray()],
        ], $output->toArray());
    }

    public function testToArrayReturnsEmptyWikisByDefault(): void
    {
        $output = new SearchMasterWikisOutput();

        $this->assertSame(['wikis' => []], $output->toArray());
    }
}
