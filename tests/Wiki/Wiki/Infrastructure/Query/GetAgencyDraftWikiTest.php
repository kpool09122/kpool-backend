<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\AgencyWikiBasicReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\GetAgencyDraftWiki\GetAgencyDraftWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetAgencyDraftWiki\GetAgencyDraftWikiInterface;
use Tests\Helper\CreateDraftWiki;
use Tests\Helper\CreateImage;
use Tests\Helper\CreateWiki;
use Tests\TestCase;

class GetAgencyDraftWikiTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsDraftAgencyWiki(): void
    {
        CreateWiki::create(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f401',
            'agency',
            [
                'slug' => 'ag-jyp-entertainment',
                'language' => 'ko',
                'version' => 3,
            ],
        );

        CreateImage::create('01965bb2-bcc9-7c6f-8b90-89f7f217f404', [
            'image_path' => '/images/wiki/agency-hero.jpg',
            'alt_text' => 'JYP Entertainment hero image',
        ]);

        CreateDraftWiki::create(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f402',
            'agency',
            [
                'published_wiki_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f401',
                'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f405',
                'slug' => 'ag-jyp-entertainment',
                'language' => 'ko',
                'image_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f404',
                'theme_color' => '#1A1A1A',
                'sections' => json_encode([
                    [
                        'type' => 'section',
                        'title' => 'Overview',
                        'display_order' => 1,
                        'contents' => [
                            [
                                'block_type' => 'image',
                                'display_order' => 1,
                                'image_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f404',
                                'caption' => 'Agency image',
                                'alt' => null,
                            ],
                            [
                                'block_type' => 'image_gallery',
                                'display_order' => 2,
                                'image_identifiers' => ['01965bb2-bcc9-7c6f-8b90-89f7f217f404'],
                                'caption' => 'Agency gallery',
                            ],
                        ],
                    ],
                ]),
            ],
            [
                'name' => 'JYP Entertainment',
                'normalized_name' => 'jypentertainment',
                'ceo' => 'J.Y. Park',
                'normalized_ceo' => 'jypark',
                'founded_in' => '1997-04-25',
                'parent_agency_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f403',
                'status' => 'active',
                'official_website' => 'https://www.jype.com',
                'social_links' => json_encode([
                    'https://twitter.com/jypnation',
                    'https://www.instagram.com/jypentertainment/',
                ]),
            ],
        );

        $useCase = $this->app->make(GetAgencyDraftWikiInterface::class);
        $readModel = $useCase->process(new GetAgencyDraftWikiInput(new Slug('ag-jyp-entertainment'), Language::KOREAN));

        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f402', $readModel->wikiIdentifier());
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f405', $readModel->translationSetIdentifier());
        $this->assertSame('ag-jyp-entertainment', $readModel->slug());
        $this->assertSame('ko', $readModel->language());
        $this->assertSame('agency', $readModel->resourceType());
        $this->assertSame('#1A1A1A', $readModel->themeColor());
        $this->assertSame([
            'imageIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f404',
            'src' => 'http://127.0.0.1:8080/images/wiki/agency-hero.jpg',
            'alt' => 'JYP Entertainment hero image',
        ], $readModel->heroImage());
        $this->assertInstanceOf(AgencyWikiBasicReadModel::class, $readModel->basic());
        $this->assertSame('JYP Entertainment', $readModel->basic()['name']);
        $this->assertSame('J.Y. Park', $readModel->basic()['ceo']);
        $this->assertSame('1997-04-25', $readModel->basic()['foundedIn']);
        $this->assertSame('https://twitter.com/jypnation', $readModel->basic()['socialLinks'][0]);
        $this->assertSame('http://127.0.0.1:8080/images/wiki/agency-hero.jpg', $readModel->sections()[0]['contents'][0]['src']);
        $this->assertSame('JYP Entertainment hero image', $readModel->sections()[0]['contents'][0]['alt']);
        $this->assertSame('http://127.0.0.1:8080/images/wiki/agency-hero.jpg', $readModel->sections()[0]['contents'][1]['images'][0]['src']);
    }

    #[Group('useDb')]
    public function testProcessReturnsNullableOptionalAgencyBasicValues(): void
    {
        CreateDraftWiki::create(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f502',
            'agency',
            [
                'slug' => 'ag-nullable-basic',
                'language' => 'en',
                'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f503',
            ],
            [
                'name' => 'Nullable Agency',
                'normalized_name' => 'nullable agency',
                'official_website' => null,
            ],
        );

        $useCase = $this->app->make(GetAgencyDraftWikiInterface::class);
        $readModel = $useCase->process(new GetAgencyDraftWikiInput(new Slug('ag-nullable-basic'), Language::ENGLISH));

        $this->assertNull($readModel->basic()['officialWebsite']);
    }

    #[Group('useDb')]
    public function testProcessThrowsWhenDraftAgencyWikiDoesNotExist(): void
    {
        $useCase = $this->app->make(GetAgencyDraftWikiInterface::class);

        $this->expectException(WikiNotFoundException::class);

        $useCase->process(new GetAgencyDraftWikiInput(new Slug('ag-jyp-entertainment'), Language::KOREAN));
    }
}
