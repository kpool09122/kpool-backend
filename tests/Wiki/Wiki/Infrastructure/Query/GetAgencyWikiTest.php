<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Query;

use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\AgencyWikiBasicReadModel;
use Source\Wiki\Wiki\Application\UseCase\Query\GetAgencyWiki\GetAgencyWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetAgencyWiki\GetAgencyWikiInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiReadModel;
use Tests\Helper\CreateWiki;
use Tests\TestCase;

class GetAgencyWikiTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsAgencyWiki(): void
    {
        CreateWiki::create(
            '01965bb2-bcc9-7c6f-8b90-89f7f217f401',
            'agency',
            [
                'slug' => 'ag-jyp-entertainment',
                'language' => 'ko',
                'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f405',
                'version' => 3,
                'image_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f404',
                'theme_color' => '#1A1A1A',
                'sections' => json_encode([
                    [
                        'id' => 'overview',
                        'type' => 'plaintext',
                        'title' => 'Overview',
                        'content' => 'Published sample for checking the agency wiki state.',
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

        $useCase = $this->app->make(GetAgencyWikiInterface::class);
        $readModel = $useCase->process(new GetAgencyWikiInput(new Slug('ag-jyp-entertainment'), Language::KOREAN));

        $this->assertInstanceOf(WikiReadModel::class, $readModel);
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f401', $readModel->wikiIdentifier());
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f405', $readModel->translationSetIdentifier());
        $this->assertSame('ag-jyp-entertainment', $readModel->slug());
        $this->assertSame('ko', $readModel->language());
        $this->assertSame('agency', $readModel->resourceType());
        $this->assertSame(3, $readModel->version());
        $this->assertSame('#1A1A1A', $readModel->themeColor());
        $this->assertSame(['imageIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f404'], $readModel->heroImage());
        $this->assertInstanceOf(AgencyWikiBasicReadModel::class, $readModel->basic());
        $this->assertSame('JYP Entertainment', $readModel->basic()['name']);
        $this->assertSame('J.Y. Park', $readModel->basic()['ceo']);
        $this->assertSame('1997-04-25', $readModel->basic()['foundedIn']);
        $this->assertSame('https://twitter.com/jypnation', $readModel->basic()['socialLinks'][0]);
        $this->assertSame('overview', $readModel->sections()[0]['id']);
    }

    #[Group('useDb')]
    public function testProcessThrowsWhenAgencyWikiDoesNotExist(): void
    {
        $useCase = $this->app->make(GetAgencyWikiInterface::class);

        $this->expectException(WikiNotFoundException::class);

        $useCase->process(new GetAgencyWikiInput(new Slug('ag-jyp-entertainment'), Language::KOREAN));
    }
}
