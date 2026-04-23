<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query;

use Source\Wiki\Wiki\Application\UseCase\Query\TalentDraftWikiReadModel;
use Tests\TestCase;

class TalentDraftWikiReadModelTest extends TestCase
{
    public function test__construct(): void
    {
        $readModel = new TalentDraftWikiReadModel(
            wikiIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            slug: 'tl-chaeyoung',
            language: 'ko',
            resourceType: 'talent',
            version: 1,
            themeColor: '#FE5F8F',
            heroImage: [
                'imageIdentifier' => null,
            ],
            basic: [
                'name' => '채영',
                'normalizedName' => 'chaeyoung',
                'realName' => '손채영',
                'normalizedRealName' => 'sonchaeyoung',
                'birthday' => '1999-04-23',
                'agencyIdentifier' => null,
                'emoji' => '',
                'representativeSymbol' => 'Strawberry Princess',
                'position' => 'rapper',
                'mbti' => 'infp',
                'zodiacSign' => 'taurus',
                'englishLevel' => null,
                'height' => 159,
                'bloodType' => 'B',
                'fandomName' => 'ONCE',
                'profileImageIdentifier' => null,
                'groups' => [
                    [
                        'wikiIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f002',
                        'slug' => 'gr-twice',
                        'language' => 'ko',
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
                ],
            ],
            sections: [
                [
                    'id' => 'overview',
                    'type' => 'plaintext',
                    'title' => 'Overview',
                    'content' => 'Draft sample for checking the talent wiki editor state.',
                ],
            ],
        );

        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f101', $readModel->wikiIdentifier());
        $this->assertSame('tl-chaeyoung', $readModel->slug());
        $this->assertSame('ko', $readModel->language());
        $this->assertSame('talent', $readModel->resourceType());
        $this->assertSame(1, $readModel->version());
        $this->assertSame('#FE5F8F', $readModel->themeColor());
        $this->assertSame(['imageIdentifier' => null], $readModel->heroImage());
        $this->assertSame('채영', $readModel->basic()['name']);
        $this->assertSame('TWICE', $readModel->basic()['groups'][0]['name']);
        $this->assertSame('overview', $readModel->sections()[0]['id']);
        $this->assertSame([
            'wikiIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            'slug' => 'tl-chaeyoung',
            'language' => 'ko',
            'resourceType' => 'talent',
            'version' => 1,
            'themeColor' => '#FE5F8F',
            'heroImage' => [
                'imageIdentifier' => null,
            ],
            'basic' => [
                'name' => '채영',
                'normalizedName' => 'chaeyoung',
                'realName' => '손채영',
                'normalizedRealName' => 'sonchaeyoung',
                'birthday' => '1999-04-23',
                'agencyIdentifier' => null,
                'emoji' => '',
                'representativeSymbol' => 'Strawberry Princess',
                'position' => 'rapper',
                'mbti' => 'infp',
                'zodiacSign' => 'taurus',
                'englishLevel' => null,
                'height' => 159,
                'bloodType' => 'B',
                'fandomName' => 'ONCE',
                'profileImageIdentifier' => null,
                'groups' => [
                    [
                        'wikiIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f002',
                        'slug' => 'gr-twice',
                        'language' => 'ko',
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
                ],
            ],
            'sections' => [
                [
                    'id' => 'overview',
                    'type' => 'plaintext',
                    'title' => 'Overview',
                    'content' => 'Draft sample for checking the talent wiki editor state.',
                ],
            ],
        ], $readModel->toArray());
    }
}
