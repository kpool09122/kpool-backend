<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query;

use Source\Wiki\Wiki\Application\UseCase\Query\WikiListItemReadModel;
use Tests\TestCase;

class WikiListItemReadModelTest extends TestCase
{
    public function testToArray(): void
    {
        $readModel = new WikiListItemReadModel(
            wikiIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            slug: 'tl-chaeyoung',
            language: 'ko',
            resourceType: 'talent',
            version: 2,
            themeColor: '#FE5F8F',
            name: 'Chaeyoung',
            normalizedName: 'chaeyoung',
            publishedAt: '2026-05-01T00:00:00+00:00',
            updatedAt: '2026-05-02T00:00:00+00:00',
        );

        $this->assertSame([
            'wikiIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            'slug' => 'tl-chaeyoung',
            'language' => 'ko',
            'resourceType' => 'talent',
            'version' => 2,
            'themeColor' => '#FE5F8F',
            'name' => 'Chaeyoung',
            'normalizedName' => 'chaeyoung',
            'publishedAt' => '2026-05-01T00:00:00+00:00',
            'updatedAt' => '2026-05-02T00:00:00+00:00',
        ], $readModel->toArray());
    }
}
