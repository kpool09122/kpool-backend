<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\GetAgencyDraftWiki\GetAgencyDraftWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetAgencyDraftWiki\GetAgencyDraftWikiInterface;
use Tests\Helper\CreateDraftWiki;
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
                'slug' => 'jyp-entertainment',
                'language' => 'ko',
                'version' => 3,
            ],
        );

        CreateDraftWiki::create(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f402',
            'agency',
            [
                'published_wiki_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f401',
                'slug' => 'jyp-entertainment',
                'language' => 'ko',
                'theme_color' => '#1A1A1A',
                'sections' => json_encode([
                    [
                        'id' => 'overview',
                        'type' => 'plaintext',
                        'title' => 'Overview',
                        'content' => 'Draft sample for checking the agency wiki editor state.',
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
                'logo_image_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f404',
                'official_website' => 'https://www.jype.com',
                'social_links' => json_encode([
                    'https://twitter.com/jypnation',
                    'https://www.instagram.com/jypentertainment/',
                ]),
            ],
        );

        $useCase = $this->app->make(GetAgencyDraftWikiInterface::class);
        $readModel = $useCase->process(new GetAgencyDraftWikiInput(new Slug('jyp-entertainment'), Language::KOREAN));

        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f402', $readModel->wikiIdentifier());
        $this->assertSame('jyp-entertainment', $readModel->slug());
        $this->assertSame('ko', $readModel->language());
        $this->assertSame('agency', $readModel->resourceType());
        $this->assertSame(3, $readModel->version());
        $this->assertSame('#1A1A1A', $readModel->themeColor());
        $this->assertSame(['imageIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f404'], $readModel->heroImage());
        $this->assertSame('JYP Entertainment', $readModel->basic()['name']);
        $this->assertSame('J.Y. Park', $readModel->basic()['ceo']);
        $this->assertSame('1997-04-25', $readModel->basic()['foundedIn']);
        $this->assertSame('https://twitter.com/jypnation', $readModel->basic()['socialLinks'][0]);
        $this->assertSame('overview', $readModel->sections()[0]['id']);
    }

    #[Group('useDb')]
    public function testProcessThrowsWhenDraftAgencyWikiDoesNotExist(): void
    {
        $useCase = $this->app->make(GetAgencyDraftWikiInterface::class);

        $this->expectException(WikiNotFoundException::class);

        $useCase->process(new GetAgencyDraftWikiInput(new Slug('jyp-entertainment'), Language::KOREAN));
    }
}
