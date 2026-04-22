<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\GetSongDraftWiki\GetSongDraftWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetSongDraftWiki\GetSongDraftWikiInterface;
use Tests\Helper\CreateDraftWiki;
use Tests\Helper\CreateWiki;
use Tests\TestCase;

class GetSongDraftWikiTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsDraftSongWiki(): void
    {
        CreateWiki::create(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f002',
            'group',
            [
                'slug' => 'twice',
                'language' => 'ko',
                'version' => 1,
            ],
            [
                'name' => 'TWICE',
                'normalized_name' => 'twice',
                'group_type' => 'girl_group',
                'status' => 'active',
                'generation' => '3',
                'debut_date' => '2015-10-20',
                'fandom_name' => 'ONCE',
                'official_colors' => json_encode(['#FE5F8F', '#FEE500']),
                'representative_symbol' => 'Candy Bong',
            ],
        );
        CreateWiki::create(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            'talent',
            [
                'slug' => 'chaeyoung',
                'language' => 'ko',
                'version' => 1,
            ],
            [
                'name' => '채영',
                'normalized_name' => 'chaeyoung',
                'real_name' => '손채영',
                'normalized_real_name' => 'sonchaeyoung',
                'birthday' => '1999-04-23',
                'representative_symbol' => 'Strawberry Princess',
                'position' => 'rapper',
                'mbti' => 'infp',
                'zodiac_sign' => 'taurus',
                'height' => 159,
                'blood_type' => 'B',
                'fandom_name' => 'ONCE',
            ],
        );
        CreateWiki::create(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f201',
            'song',
            [
                'slug' => 'signal',
                'language' => 'ko',
                'version' => 1,
            ],
            [
                'name' => 'TT',
                'normalized_name' => 'tt',
                'song_type' => 'title_track',
                'genres' => json_encode(['dance_pop']),
                'group_identifiers' => json_encode(['01965bb2-bcc9-7c6f-8b90-89f7f217f002']),
                'talent_identifiers' => json_encode(['01965bb2-bcc9-7c6f-8b90-89f7f217f101']),
                'release_date' => '2016-10-24',
                'album_name' => 'TWICEcoaster: Lane 1',
                'lyricist' => 'Black Eyed Pilseung',
                'normalized_lyricist' => 'black eyed pilseung',
                'composer' => 'Black Eyed Pilseung',
                'normalized_composer' => 'black eyed pilseung',
                'arranger' => 'Rado',
                'normalized_arranger' => 'rado',
            ],
        );
        CreateDraftWiki::create(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f301',
            'song',
            [
                'published_wiki_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f201',
                'slug' => 'signal',
                'language' => 'ko',
                'theme_color' => '#FE5F8F',
                'sections' => json_encode([
                    [
                        'id' => 'overview',
                        'type' => 'plaintext',
                        'title' => 'Overview',
                        'content' => 'Draft sample for checking the song wiki editor state.',
                    ],
                ]),
            ],
            [
                'name' => 'TT',
                'normalized_name' => 'tt',
                'song_type' => 'title_track',
                'genres' => json_encode(['dance_pop']),
                'group_identifiers' => json_encode(['01965bb2-bcc9-7c6f-8b90-89f7f217f002']),
                'talent_identifiers' => json_encode(['01965bb2-bcc9-7c6f-8b90-89f7f217f101']),
                'release_date' => '2016-10-24',
                'album_name' => 'TWICEcoaster: Lane 1',
                'lyricist' => 'Black Eyed Pilseung',
                'normalized_lyricist' => 'black eyed pilseung',
                'composer' => 'Black Eyed Pilseung',
                'normalized_composer' => 'black eyed pilseung',
                'arranger' => 'Rado',
                'normalized_arranger' => 'rado',
            ],
        );

        $useCase = $this->app->make(GetSongDraftWikiInterface::class);
        $readModel = $useCase->process(new GetSongDraftWikiInput(new Slug('signal'), Language::KOREAN));

        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f301', $readModel->wikiIdentifier());
        $this->assertSame('signal', $readModel->slug());
        $this->assertSame('ko', $readModel->language());
        $this->assertSame('song', $readModel->resourceType());
        $this->assertSame(1, $readModel->version());
        $this->assertSame('#FE5F8F', $readModel->themeColor());
        $this->assertSame(['imageIdentifier' => null], $readModel->heroImage());
        $this->assertSame('TT', $readModel->basic()['name']);
        $this->assertSame('title_track', $readModel->basic()['songType']);
        $this->assertSame(['dance_pop'], $readModel->basic()['genres']);
        $this->assertSame('TWICE', $readModel->basic()['groups'][0]['name']);
        $this->assertSame('girl_group', $readModel->basic()['groups'][0]['groupType']);
        $this->assertSame('채영', $readModel->basic()['talents'][0]['name']);
        $this->assertSame('rapper', $readModel->basic()['talents'][0]['position']);
        $this->assertSame('overview', $readModel->sections()[0]['id']);
    }

    #[Group('useDb')]
    public function testProcessThrowsWhenDraftSongWikiDoesNotExist(): void
    {
        $useCase = $this->app->make(GetSongDraftWikiInterface::class);

        $this->expectException(WikiNotFoundException::class);

        $useCase->process(new GetSongDraftWikiInput(new Slug('signal'), Language::KOREAN));
    }
}
