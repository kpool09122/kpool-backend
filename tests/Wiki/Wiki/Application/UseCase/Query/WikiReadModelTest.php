<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query;

use Source\Wiki\Wiki\Application\UseCase\Query\GroupWikiBasicReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiReadModel;
use Tests\TestCase;

class WikiReadModelTest extends TestCase
{
    public function test__construct(): void
    {
        $readModel = new WikiReadModel(
            wikiIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f002',
            translationSetIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f003',
            slug: 'gr-twice',
            language: 'ko',
            resourceType: 'group',
            version: 2,
            themeColor: '#FE5F8F',
            heroImage: [
                'imageIdentifier' => null,
            ],
            basic: [
                'name' => 'TWICE',
                'normalizedName' => 'twice',
                'agencyIdentifier' => null,
                'groupType' => 'girl_group',
                'status' => 'active',
                'generation' => '3',
                'debutDate' => '2015-10-20',
                'disbandDate' => null,
                'fandomName' => 'ONCE',
                'officialColors' => ['#FE5F8F', '#FEE500'],
                'emoji' => '',
                'representativeSymbol' => 'Candy Bong',
                'mainImageIdentifier' => null,
            ],
            sections: [
                [
                    'id' => 'overview',
                    'type' => 'plaintext',
                    'title' => 'Overview',
                    'content' => 'Published sample for checking the TWICE group wiki state.',
                ],
            ],
        );

        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f002', $readModel->wikiIdentifier());
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f003', $readModel->translationSetIdentifier());
        $this->assertSame('gr-twice', $readModel->slug());
        $this->assertSame('ko', $readModel->language());
        $this->assertSame('group', $readModel->resourceType());
        $this->assertSame(2, $readModel->version());
        $this->assertSame('#FE5F8F', $readModel->themeColor());
        $this->assertSame(['imageIdentifier' => null], $readModel->heroImage());
        $this->assertInstanceOf(GroupWikiBasicReadModel::class, $readModel->basic());
        $this->assertSame('TWICE', $readModel->basic()['name']);
        $this->assertSame('overview', $readModel->sections()[0]['id']);
        $this->assertSame([
            'wikiIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f002',
            'translationSetIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f003',
            'slug' => 'gr-twice',
            'language' => 'ko',
            'resourceType' => 'group',
            'version' => 2,
            'themeColor' => '#FE5F8F',
            'heroImage' => [
                'imageIdentifier' => null,
            ],
            'basic' => [
                'name' => 'TWICE',
                'normalizedName' => 'twice',
                'agencyIdentifier' => null,
                'groupType' => 'girl_group',
                'status' => 'active',
                'generation' => '3',
                'debutDate' => '2015-10-20',
                'disbandDate' => null,
                'fandomName' => 'ONCE',
                'officialColors' => ['#FE5F8F', '#FEE500'],
                'emoji' => '',
                'representativeSymbol' => 'Candy Bong',
                'mainImageIdentifier' => null,
            ],
            'sections' => [
                [
                    'id' => 'overview',
                    'type' => 'plaintext',
                    'title' => 'Overview',
                    'content' => 'Published sample for checking the TWICE group wiki state.',
                ],
            ],
        ], $readModel->toArray());
    }
}
