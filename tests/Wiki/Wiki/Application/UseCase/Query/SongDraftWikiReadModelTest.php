<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query;

use Source\Wiki\Wiki\Application\UseCase\Query\SongDraftWikiReadModel;
use Tests\TestCase;

class SongDraftWikiReadModelTest extends TestCase
{
    public function test__construct(): void
    {
        $readModel = new SongDraftWikiReadModel(
            wikiIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f301',
            slug: 'sg-tt',
            language: 'ko',
            resourceType: 'song',
            version: 1,
            themeColor: '#FE5F8F',
            heroImage: [
                'imageIdentifier' => null,
            ],
            basic: [
                'name' => 'TT',
                'normalizedName' => 'tt',
                'songType' => 'title_track',
                'genres' => ['dance_pop'],
                'agencyIdentifier' => null,
                'releaseDate' => '2016-10-24',
                'albumName' => 'TWICEcoaster: Lane 1',
                'coverImageIdentifier' => null,
                'lyricist' => 'Black Eyed Pilseung',
                'normalizedLyricist' => 'black eyed pilseung',
                'composer' => 'Black Eyed Pilseung',
                'normalizedComposer' => 'black eyed pilseung',
                'arranger' => 'Rado',
                'normalizedArranger' => 'rado',
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
                'talents' => [
                    [
                        'wikiIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
                        'slug' => 'tl-chaeyoung',
                        'language' => 'ko',
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
                    ],
                ],
            ],
            sections: [
                [
                    'id' => 'overview',
                    'type' => 'plaintext',
                    'title' => 'Overview',
                    'content' => 'Draft sample for checking the song wiki editor state.',
                ],
            ],
        );

        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f301', $readModel->wikiIdentifier());
        $this->assertSame('sg-tt', $readModel->slug());
        $this->assertSame('ko', $readModel->language());
        $this->assertSame('song', $readModel->resourceType());
        $this->assertSame(1, $readModel->version());
        $this->assertSame('#FE5F8F', $readModel->themeColor());
        $this->assertSame(['imageIdentifier' => null], $readModel->heroImage());
        $this->assertSame('TT', $readModel->basic()['name']);
        $this->assertSame('TWICE', $readModel->basic()['groups'][0]['name']);
        $this->assertSame('채영', $readModel->basic()['talents'][0]['name']);
        $this->assertSame('overview', $readModel->sections()[0]['id']);
        $this->assertSame([
            'wikiIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f301',
            'slug' => 'sg-tt',
            'language' => 'ko',
            'resourceType' => 'song',
            'version' => 1,
            'themeColor' => '#FE5F8F',
            'heroImage' => [
                'imageIdentifier' => null,
            ],
            'basic' => [
                'name' => 'TT',
                'normalizedName' => 'tt',
                'songType' => 'title_track',
                'genres' => ['dance_pop'],
                'agencyIdentifier' => null,
                'releaseDate' => '2016-10-24',
                'albumName' => 'TWICEcoaster: Lane 1',
                'coverImageIdentifier' => null,
                'lyricist' => 'Black Eyed Pilseung',
                'normalizedLyricist' => 'black eyed pilseung',
                'composer' => 'Black Eyed Pilseung',
                'normalizedComposer' => 'black eyed pilseung',
                'arranger' => 'Rado',
                'normalizedArranger' => 'rado',
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
                'talents' => [
                    [
                        'wikiIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
                        'slug' => 'tl-chaeyoung',
                        'language' => 'ko',
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
                    ],
                ],
            ],
            'sections' => [
                [
                    'id' => 'overview',
                    'type' => 'plaintext',
                    'title' => 'Overview',
                    'content' => 'Draft sample for checking the song wiki editor state.',
                ],
            ],
        ], $readModel->toArray());
    }
}
