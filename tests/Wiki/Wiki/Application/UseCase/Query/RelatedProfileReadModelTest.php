<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\UseCase\Query;

use Source\Wiki\Wiki\Application\UseCase\Query\RelatedProfileReadModel;
use Tests\TestCase;

class RelatedProfileReadModelTest extends TestCase
{
    public function testToArrayReturnsRelatedProfilePayload(): void
    {
        $readModel = new RelatedProfileReadModel(
            wikiIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f001',
            slug: 'tl-momo',
            language: 'ko',
            resourceType: 'talent',
            name: 'Momo',
            normalizedName: 'momo',
            imageIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f002',
            imageUrl: 'http://127.0.0.1:8080/images/test/momo.jpg',
            imageAltText: 'Momo profile',
        );

        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f001', $readModel->wikiIdentifier());
        $this->assertSame([
            'wikiIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f001',
            'slug' => 'tl-momo',
            'language' => 'ko',
            'resourceType' => 'talent',
            'name' => 'Momo',
            'normalizedName' => 'momo',
            'imageIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f002',
            'imageUrl' => 'http://127.0.0.1:8080/images/test/momo.jpg',
            'imageAltText' => 'Momo profile',
        ], $readModel->toArray());
    }

    public function testToArrayAllowsNullableImageFields(): void
    {
        $readModel = new RelatedProfileReadModel(
            wikiIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f003',
            slug: 'gr-twice',
            language: 'ko',
            resourceType: 'group',
            name: 'TWICE',
            normalizedName: 'twice',
            imageIdentifier: null,
            imageUrl: null,
            imageAltText: null,
        );

        $this->assertSame([
            'wikiIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f003',
            'slug' => 'gr-twice',
            'language' => 'ko',
            'resourceType' => 'group',
            'name' => 'TWICE',
            'normalizedName' => 'twice',
            'imageIdentifier' => null,
            'imageUrl' => null,
            'imageAltText' => null,
        ], $readModel->toArray());
    }
}
