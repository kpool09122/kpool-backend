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
            translationSetIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f103',
            slug: 'tl-chaeyoung',
            language: 'ko',
            resourceType: 'talent',
            version: 2,
            themeColor: '#fe5f8f',
            imageIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f104',
            imageUrl: 'http://localhost/storage/wiki/example.jpg',
            imageAltText: 'Chaeyoung profile image',
            isHidden: false,
            name: 'Chaeyoung',
            normalizedName: 'chaeyoung',
            publishedAt: '2026-05-01T00:00:00+00:00',
            updatedAt: '2026-05-02T00:00:00+00:00',
            isOfficial: true,
            title: 'Chaeyoung Wiki',
            metaDescription: 'Profile for Chaeyoung.',
            keywords: ['Chaeyoung', 'TWICE'],
            fontStyle: 'ja_pop',
        );

        $this->assertSame([
            'wikiIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            'translationSetIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f103',
            'slug' => 'tl-chaeyoung',
            'language' => 'ko',
            'resourceType' => 'talent',
            'version' => 2,
            'isOfficial' => true,
            'themeColor' => '#fe5f8f',
            'fontStyle' => 'ja_pop',
            'title' => 'Chaeyoung Wiki',
            'metaDescription' => 'Profile for Chaeyoung.',
            'keywords' => ['Chaeyoung', 'TWICE'],
            'imageIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f104',
            'imageUrl' => 'http://localhost/storage/wiki/example.jpg',
            'imageAltText' => 'Chaeyoung profile image',
            'isHidden' => false,
            'name' => 'Chaeyoung',
            'normalizedName' => 'chaeyoung',
            'publishedAt' => '2026-05-01T00:00:00+00:00',
            'updatedAt' => '2026-05-02T00:00:00+00:00',
        ], $readModel->toArray());
    }
}
