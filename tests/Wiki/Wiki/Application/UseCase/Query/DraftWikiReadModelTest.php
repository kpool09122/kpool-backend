<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query;

use Source\Wiki\Wiki\Application\UseCase\Query\DraftWikiReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\GroupWikiBasicReadModel;
use Tests\TestCase;

class DraftWikiReadModelTest extends TestCase
{
    public function test__construct(): void
    {
        $readModel = new DraftWikiReadModel(
            wikiIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f002',
            translationSetIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f003',
            slug: 'gr-twice',
            language: 'ko',
            resourceType: 'group',
            themeColor: '#FE5F8F',
            heroImage: [
                'imageIdentifier' => null,
                'src' => null,
                'alt' => null,
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
            ],
            sections: [
                [
                    'id' => 'overview',
                    'type' => 'plaintext',
                    'title' => 'Overview',
                    'content' => 'Draft sample for checking the TWICE group wiki editor state.',
                ],
            ],
            status: 'under_review',
            rejectionReason: '内容が不十分です',
            title: 'TWICE Draft Wiki',
            metaDescription: 'Draft profile and history for TWICE.',
            keywords: ['TWICE', 'draft'],
        );

        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f002', $readModel->wikiIdentifier());
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f003', $readModel->translationSetIdentifier());
        $this->assertSame('gr-twice', $readModel->slug());
        $this->assertSame('ko', $readModel->language());
        $this->assertSame('group', $readModel->resourceType());
        $this->assertSame('under_review', $readModel->status());
        $this->assertSame('内容が不十分です', $readModel->rejectionReason());
        $this->assertSame('#FE5F8F', $readModel->themeColor());
        $this->assertSame('TWICE Draft Wiki', $readModel->title());
        $this->assertSame('Draft profile and history for TWICE.', $readModel->metaDescription());
        $this->assertSame(['TWICE', 'draft'], $readModel->keywords());
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
            'status' => 'under_review',
            'rejectionReason' => '内容が不十分です',
            'themeColor' => '#FE5F8F',
            'title' => 'TWICE Draft Wiki',
            'metaDescription' => 'Draft profile and history for TWICE.',
            'keywords' => ['TWICE', 'draft'],
            'heroImage' => [
                'imageIdentifier' => null,
                'src' => null,
                'alt' => null,
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
            ],
            'sections' => [
                [
                    'id' => 'overview',
                    'type' => 'plaintext',
                    'title' => 'Overview',
                    'content' => 'Draft sample for checking the TWICE group wiki editor state.',
                ],
            ],
        ], $readModel->toArray());
    }
}
