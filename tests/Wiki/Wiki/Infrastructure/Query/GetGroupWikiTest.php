<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\GetGroupWiki\GetGroupWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetGroupWiki\GetGroupWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\GroupWikiBasicReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiReadModel;
use Tests\Helper\CreateImage;
use Tests\Helper\CreateWiki;
use Tests\TestCase;

class GetGroupWikiTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsGroupWiki(): void
    {
        CreateWiki::create(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f002',
            'group',
            [
                'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f003',
                'slug' => 'gr-twice',
                'language' => 'ko',
                'version' => 2,
                'theme_color' => '#FE5F8F',
                'sections' => json_encode([
                    [
                        'id' => 'overview',
                        'type' => 'plaintext',
                        'title' => 'Overview',
                        'content' => 'Published sample for checking the TWICE group wiki state.',
                    ],
                ]),
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

        $useCase = $this->app->make(GetGroupWikiInterface::class);
        $readModel = $useCase->process(new GetGroupWikiInput(new Slug('gr-twice'), Language::KOREAN));

        $this->assertInstanceOf(WikiReadModel::class, $readModel);
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f002', $readModel->wikiIdentifier());
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f003', $readModel->translationSetIdentifier());
        $this->assertSame('gr-twice', $readModel->slug());
        $this->assertSame('ko', $readModel->language());
        $this->assertSame('group', $readModel->resourceType());
        $this->assertSame(2, $readModel->version());
        $this->assertSame('#FE5F8F', $readModel->themeColor());
        $this->assertSame(['imageIdentifier' => null, 'src' => null, 'alt' => null], $readModel->heroImage());
        $this->assertInstanceOf(GroupWikiBasicReadModel::class, $readModel->basic());
        $this->assertSame('TWICE', $readModel->basic()['name']);
        $this->assertSame('girl_group', $readModel->basic()['groupType']);
        $this->assertSame(['#FE5F8F', '#FEE500'], $readModel->basic()['officialColors']);
        $this->assertSame('overview', $readModel->sections()[0]['id']);
    }

    #[Group('useDb')]
    public function testProcessReturnsImageUrlsForCamelCaseSectionImages(): void
    {
        CreateImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f404', [
            'image_path' => '/images/wiki/group-section.jpg',
            'alt_text' => 'TWICE section image',
        ]);

        CreateWiki::create(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f402',
            'group',
            [
                'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f403',
                'slug' => 'gr-twice-camel-image',
                'language' => 'ko',
                'sections' => json_encode([
                    [
                        'type' => 'section',
                        'title' => 'Gallery',
                        'display_order' => 1,
                        'contents' => [
                            [
                                'blockType' => 'image',
                                'displayOrder' => 1,
                                'imageIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f404',
                                'caption' => 'Section image',
                                'alt' => null,
                            ],
                            [
                                'blockType' => 'image_gallery',
                                'displayOrder' => 2,
                                'imageIdentifiers' => ['01965bb2-bcc9-7c6f-8b90-89f7f217f404'],
                                'caption' => 'Section gallery',
                            ],
                        ],
                    ],
                ]),
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

        $useCase = $this->app->make(GetGroupWikiInterface::class);
        $readModel = $useCase->process(new GetGroupWikiInput(new Slug('gr-twice-camel-image'), Language::KOREAN));

        $this->assertSame(
            'http://127.0.0.1:8080/images/wiki/group-section.jpg',
            $readModel->sections()[0]['contents'][0]['src'],
        );
        $this->assertSame('TWICE section image', $readModel->sections()[0]['contents'][0]['alt']);
        $this->assertSame(
            'http://127.0.0.1:8080/images/wiki/group-section.jpg',
            $readModel->sections()[0]['contents'][1]['images'][0]['src'],
        );
        $this->assertSame('TWICE section image', $readModel->sections()[0]['contents'][1]['images'][0]['alt']);
    }

    #[Group('useDb')]
    public function testProcessReturnsProfilesForProfileCardListBlocks(): void
    {
        CreateImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f504', [
            'image_path' => '/images/wiki/momo.jpg',
            'alt_text' => 'Momo profile image',
        ]);
        CreateWiki::create(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f503',
            'talent',
            [
                'slug' => 'tl-momo',
                'language' => 'ko',
                'image_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f504',
            ],
            [
                'name' => 'Momo',
                'normalized_name' => 'momo',
            ],
        );
        CreateWiki::create(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f502',
            'group',
            [
                'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f505',
                'slug' => 'gr-twice-profiles',
                'language' => 'ko',
                'sections' => json_encode([
                    [
                        'type' => 'section',
                        'title' => 'Members',
                        'display_order' => 1,
                        'contents' => [
                            [
                                'block_type' => 'profile_card_list',
                                'display_order' => 1,
                                'wiki_identifiers' => ['01965bb2-bcc9-7c6f-8b90-89f7f217f503'],
                                'title' => 'Members',
                            ],
                        ],
                    ],
                ]),
            ],
            [
                'name' => 'TWICE',
                'normalized_name' => 'twice',
                'group_type' => 'girl_group',
                'generation' => '3',
            ],
        );

        $useCase = $this->app->make(GetGroupWikiInterface::class);
        $readModel = $useCase->process(new GetGroupWikiInput(new Slug('gr-twice-profiles'), Language::KOREAN));
        $block = $readModel->sections()[0]['contents'][0];

        $this->assertSame(['01965bb2-bcc9-7c6f-8b90-89f7f217f503'], $block['wikiIdentifiers']);
        $this->assertSame('talent', $block['relatedResourceType']);
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f503', $block['profiles'][0]['wikiIdentifier']);
        $this->assertSame('tl-momo', $block['profiles'][0]['slug']);
        $this->assertSame('talent', $block['profiles'][0]['resourceType']);
        $this->assertSame('Momo', $block['profiles'][0]['name']);
        $this->assertSame('http://127.0.0.1:8080/images/wiki/momo.jpg', $block['profiles'][0]['imageUrl']);
        $this->assertSame('Momo profile image', $block['profiles'][0]['imageAltText']);
    }

    #[Group('useDb')]
    public function testProcessThrowsWhenGroupWikiDoesNotExist(): void
    {
        $useCase = $this->app->make(GetGroupWikiInterface::class);

        $this->expectException(WikiNotFoundException::class);

        $useCase->process(new GetGroupWikiInput(new Slug('gr-twice'), Language::KOREAN));
    }
}
