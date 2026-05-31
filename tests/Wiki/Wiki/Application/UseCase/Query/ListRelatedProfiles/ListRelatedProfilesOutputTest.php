<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query\ListRelatedProfiles;

use Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedProfiles\ListRelatedProfilesOutput;
use Source\Wiki\Wiki\Application\UseCase\Query\RelatedProfileReadModel;
use Tests\TestCase;

class ListRelatedProfilesOutputTest extends TestCase
{
    public function testToArrayReturnsProfiles(): void
    {
        $output = new ListRelatedProfilesOutput();
        $output->output([
            new RelatedProfileReadModel(
                wikiIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f001',
                slug: 'tl-momo',
                language: 'ko',
                resourceType: 'talent',
                name: 'Momo',
                normalizedName: 'momo',
                imageIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f002',
                imageUrl: 'http://127.0.0.1:8080/images/test/momo.jpg',
                imageAltText: 'Momo profile',
            ),
        ]);

        $this->assertSame([
            'profiles' => [
                [
                    'wikiIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f001',
                    'slug' => 'tl-momo',
                    'language' => 'ko',
                    'resourceType' => 'talent',
                    'name' => 'Momo',
                    'normalizedName' => 'momo',
                    'imageIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f002',
                    'imageUrl' => 'http://127.0.0.1:8080/images/test/momo.jpg',
                    'imageAltText' => 'Momo profile',
                ],
            ],
        ], $output->toArray());
    }

    public function testToArrayReturnsEmptyProfilesByDefault(): void
    {
        $output = new ListRelatedProfilesOutput();

        $this->assertSame(['profiles' => []], $output->toArray());
    }
}
