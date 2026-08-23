<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\ListRelatedWikis;

use Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedWikis\ListRelatedWikisOutput;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiListItemReadModel;
use Tests\TestCase;

class ListRelatedWikisOutputTest extends TestCase
{
    public function testToArrayReturnsWikis(): void
    {
        $output = new ListRelatedWikisOutput();
        $output->output([
            new WikiListItemReadModel(
                wikiIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
                translationSetIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f102',
                slug: 'gr-twice',
                language: 'ko',
                resourceType: 'group',
                version: 1,
                themeColor: null,
                imageIdentifier: null,
                imageUrl: null,
                imageAltText: null,
                isHidden: null,
                name: 'TWICE',
                normalizedName: 'twice',
                publishedAt: '2026-04-01T00:00:00+00:00',
                updatedAt: '2026-04-02T00:00:00+00:00',
            ),
        ]);

        $payload = $output->toArray();

        $this->assertSame('gr-twice', $payload['wikis'][0]['slug']);
        $this->assertSame('group', $payload['wikis'][0]['resourceType']);
    }
}
