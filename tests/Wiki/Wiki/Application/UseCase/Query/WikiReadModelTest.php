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
            fontStyle: 'ja_pop',
            heroImage: [
                'imageIdentifier' => null,
                'src' => null,
                'alt' => null,
            ],
            basic: [
                'name' => 'TWICE',
                'normalizedName' => 'twice',
                'agencyIdentifier' => null,
                'agency' => null,
                'groupType' => 'girl_group',
                'status' => 'active',
                'generation' => '3',
                'debutDate' => '2015-10-20',
                'disbandDate' => null,
                'fandomName' => 'ONCE',
                'officialColors' => [['colorCode' => '#FE5F8F', 'label' => 'Apricot'], ['colorCode' => '#FEE500', 'label' => 'Yellow']],
                'emoji' => '',
                'representativeSymbol' => 'Candy Bong',
            ],
            sections: [
                [
                    'id' => 'overview',
                    'type' => 'plaintext',
                    'title' => 'Overview',
                    'content' => 'Published sample for checking the TWICE group wiki state.',
                ],
            ],
            title: 'TWICE Wiki',
            metaDescription: 'Profile and history for TWICE.',
            keywords: ['TWICE', 'K-pop'],
        );

        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f002', $readModel->wikiIdentifier());
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f003', $readModel->translationSetIdentifier());
        $this->assertSame('gr-twice', $readModel->slug());
        $this->assertSame('ko', $readModel->language());
        $this->assertSame('group', $readModel->resourceType());
        $this->assertSame(2, $readModel->version());
        $this->assertSame('#FE5F8F', $readModel->themeColor());
        $this->assertSame('ja_pop', $readModel->fontStyle());
        $this->assertSame('TWICE Wiki', $readModel->title());
        $this->assertSame('Profile and history for TWICE.', $readModel->metaDescription());
        $this->assertSame(['TWICE', 'K-pop'], $readModel->keywords());
        $this->assertSame(['imageIdentifier' => null, 'src' => null, 'alt' => null], $readModel->heroImage());
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
            'fontStyle' => 'ja_pop',
            'title' => 'TWICE Wiki',
            'metaDescription' => 'Profile and history for TWICE.',
            'keywords' => ['TWICE', 'K-pop'],
            'heroImage' => [
                'imageIdentifier' => null,
                'src' => null,
                'alt' => null,
            ],
            'basic' => [
                'name' => 'TWICE',
                'normalizedName' => 'twice',
                'agencyIdentifier' => null,
                'agency' => null,
                'groupType' => 'girl_group',
                'status' => 'active',
                'generation' => '3',
                'debutDate' => '2015-10-20',
                'disbandDate' => null,
                'fandomName' => 'ONCE',
                'officialColors' => [['colorCode' => '#FE5F8F', 'label' => 'Apricot'], ['colorCode' => '#FEE500', 'label' => 'Yellow']],
                'emoji' => '',
                'representativeSymbol' => 'Candy Bong',
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
