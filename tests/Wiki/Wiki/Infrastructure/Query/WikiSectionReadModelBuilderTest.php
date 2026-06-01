<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use PHPUnit\Framework\Attributes\Group;
use Source\Wiki\Wiki\Infrastructure\Query\WikiSectionReadModelBuilder;
use Tests\Helper\CreateImage;
use Tests\Helper\CreateWiki;
use Tests\TestCase;

class WikiSectionReadModelBuilderTest extends TestCase
{
    #[Group('useDb')]
    public function testBuildReturnsImageDetailsAndProfileCardProfiles(): void
    {
        CreateImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f701', [
            'image_path' => '/images/wiki/section.jpg',
            'alt_text' => 'Section image',
        ]);
        CreateImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f702', [
            'image_path' => '/images/wiki/momo.jpg',
            'alt_text' => 'Momo profile image',
        ]);
        CreateWiki::create(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f703',
            'talent',
            [
                'slug' => 'tl-momo',
                'language' => 'ko',
                'image_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f702',
            ],
            [
                'name' => 'Momo',
                'normalized_name' => 'momo',
            ],
        );

        $sections = WikiSectionReadModelBuilder::build([
            [
                'type' => 'section',
                'title' => 'Overview',
                'contents' => [
                    [
                        'block_type' => 'image',
                        'image_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f701',
                        'alt' => null,
                    ],
                    [
                        'block_type' => 'profile_card_list',
                        'wiki_identifiers' => ['01965bb2-bcc9-7c6f-8b90-89f7f217f703'],
                        'title' => 'Members',
                    ],
                ],
            ],
        ]);

        $imageBlock = $sections[0]['contents'][0];
        $profileBlock = $sections[0]['contents'][1];

        $this->assertSame('http://127.0.0.1:8080/images/wiki/section.jpg', $imageBlock['src']);
        $this->assertSame('Section image', $imageBlock['alt']);
        $this->assertSame(['01965bb2-bcc9-7c6f-8b90-89f7f217f703'], $profileBlock['wikiIdentifiers']);
        $this->assertSame('talent', $profileBlock['relatedResourceType']);
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f703', $profileBlock['profiles'][0]['wikiIdentifier']);
        $this->assertSame('tl-momo', $profileBlock['profiles'][0]['slug']);
        $this->assertSame('Momo', $profileBlock['profiles'][0]['name']);
        $this->assertSame('http://127.0.0.1:8080/images/wiki/momo.jpg', $profileBlock['profiles'][0]['imageUrl']);
        $this->assertSame('Momo profile image', $profileBlock['profiles'][0]['imageAltText']);
    }

    public function testBuildKeepsProfileCardListEmptyWhenIdentifiersAreMissing(): void
    {
        $sections = WikiSectionReadModelBuilder::build([
            [
                'type' => 'section',
                'title' => 'Overview',
                'contents' => [
                    [
                        'block_type' => 'profile_card_list',
                        'title' => 'Members',
                    ],
                ],
            ],
        ]);

        $profileBlock = $sections[0]['contents'][0];

        $this->assertSame([], $profileBlock['wikiIdentifiers']);
        $this->assertSame([], $profileBlock['profiles']);
        $this->assertNull($profileBlock['relatedResourceType']);
    }
}
