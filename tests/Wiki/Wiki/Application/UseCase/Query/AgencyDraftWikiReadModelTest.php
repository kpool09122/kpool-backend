<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query;

use Source\Wiki\Wiki\Application\UseCase\Query\AgencyDraftWikiReadModel;
use Tests\TestCase;

class AgencyDraftWikiReadModelTest extends TestCase
{
    public function test__construct(): void
    {
        $readModel = new AgencyDraftWikiReadModel(
            wikiIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f201',
            slug: 'jyp-entertainment',
            language: 'ko',
            resourceType: 'agency',
            version: 1,
            themeColor: '#1A1A1A',
            heroImage: [
                'imageIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f202',
            ],
            basic: [
                'name' => 'JYP Entertainment',
                'normalizedName' => 'jypentertainment',
                'ceo' => 'J.Y. Park',
                'normalizedCeo' => 'jypark',
                'foundedIn' => '1997-04-25',
                'parentAgencyIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f203',
                'status' => 'active',
                'logoImageIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f202',
                'officialWebsite' => 'https://www.jype.com',
                'socialLinks' => [
                    'https://twitter.com/jypnation',
                    'https://www.instagram.com/jypentertainment/',
                ],
            ],
            sections: [
                [
                    'id' => 'overview',
                    'type' => 'plaintext',
                    'title' => 'Overview',
                    'content' => 'Draft sample for checking the agency wiki editor state.',
                ],
            ],
        );

        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f201', $readModel->wikiIdentifier());
        $this->assertSame('jyp-entertainment', $readModel->slug());
        $this->assertSame('ko', $readModel->language());
        $this->assertSame('agency', $readModel->resourceType());
        $this->assertSame(1, $readModel->version());
        $this->assertSame('#1A1A1A', $readModel->themeColor());
        $this->assertSame(['imageIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f202'], $readModel->heroImage());
        $this->assertSame('JYP Entertainment', $readModel->basic()['name']);
        $this->assertSame('J.Y. Park', $readModel->basic()['ceo']);
        $this->assertSame('https://twitter.com/jypnation', $readModel->basic()['socialLinks'][0]);
        $this->assertSame('overview', $readModel->sections()[0]['id']);
        $this->assertSame([
            'wikiIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f201',
            'slug' => 'jyp-entertainment',
            'language' => 'ko',
            'resourceType' => 'agency',
            'version' => 1,
            'themeColor' => '#1A1A1A',
            'heroImage' => [
                'imageIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f202',
            ],
            'basic' => [
                'name' => 'JYP Entertainment',
                'normalizedName' => 'jypentertainment',
                'ceo' => 'J.Y. Park',
                'normalizedCeo' => 'jypark',
                'foundedIn' => '1997-04-25',
                'parentAgencyIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f203',
                'status' => 'active',
                'logoImageIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f202',
                'officialWebsite' => 'https://www.jype.com',
                'socialLinks' => [
                    'https://twitter.com/jypnation',
                    'https://www.instagram.com/jypentertainment/',
                ],
            ],
            'sections' => [
                [
                    'id' => 'overview',
                    'type' => 'plaintext',
                    'title' => 'Overview',
                    'content' => 'Draft sample for checking the agency wiki editor state.',
                ],
            ],
        ], $readModel->toArray());
    }
}
