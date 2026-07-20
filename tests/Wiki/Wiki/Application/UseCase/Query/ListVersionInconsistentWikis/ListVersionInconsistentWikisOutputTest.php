<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\ListVersionInconsistentWikis;

use Source\Wiki\Wiki\Application\UseCase\Query\ListVersionInconsistentWikis\ListVersionInconsistentWikisOutput;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiListItemReadModel;
use Tests\TestCase;

class ListVersionInconsistentWikisOutputTest extends TestCase
{
    public function testToArray(): void
    {
        $item = new WikiListItemReadModel(
            wikiIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            translationSetIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f103',
            slug: 'tl-chaeyoung',
            language: 'ko',
            resourceType: 'talent',
            version: 2,
            themeColor: '#FE5F8F',
            imageIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f104',
            imageUrl: 'http://localhost/storage/wiki/example.jpg',
            imageAltText: 'Chaeyoung profile image',
            isHidden: true,
            name: 'Chaeyoung',
            normalizedName: 'chaeyoung',
            publishedAt: '2026-05-01T00:00:00+00:00',
            updatedAt: '2026-05-02T00:00:00+00:00',
        );

        $output = new ListVersionInconsistentWikisOutput();
        $output->output([$item], 1, 3, 5, 2);

        $this->assertSame([
            'wikis' => [$item->toArray()],
            'current_page' => 1,
            'last_page' => 3,
            'total' => 5,
            'per_page' => 2,
        ], $output->toArray());
    }
}
