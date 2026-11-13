<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Query;

use Source\Wiki\Image\Application\UseCase\Query\ManagedImageReadModel;
use Tests\TestCase;

class ManagedImageReadModelTest extends TestCase
{
    public function testToArray(): void
    {
        $readModel = new ManagedImageReadModel(
            imageIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            url: 'https://example.com/images/talents/profile.jpg',
            resourceType: 'talent',
            translationSetIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f201',
            displayOrder: 1,
            sourceUrl: 'https://example.com/source',
            sourceName: 'Example Source',
            altText: 'Profile image',
            isHidden: false,
            uploadedAt: '2026-05-01T00:00:00+00:00',
            wiki: ['names' => ['ja' => 'Wiki'], 'slug' => 'wiki'],
            status: 'approved',
        );

        $this->assertSame([
            'imageIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            'url' => 'https://example.com/images/talents/profile.jpg',
            'resourceType' => 'talent',
            'translationSetIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f201',
            'displayOrder' => 1,
            'sourceUrl' => 'https://example.com/source',
            'sourceName' => 'Example Source',
            'author' => null,
            'license' => null,
            'altText' => 'Profile image',
            'isHidden' => false,
            'uploadedAt' => '2026-05-01T00:00:00+00:00',
            'wiki' => ['names' => ['ja' => 'Wiki'], 'slug' => 'wiki'],
            'status' => 'approved',
        ], $readModel->toArray());
    }

    public function testMissingWikiNamesAreEncodedAsAnObject(): void
    {
        $readModel = new ManagedImageReadModel(
            imageIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            url: 'https://example.com/image.jpg',
            resourceType: 'talent',
            translationSetIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f201',
            displayOrder: 0,
            sourceUrl: 'https://example.com',
            sourceName: 'Source',
            altText: '',
            isHidden: false,
            uploadedAt: null,
            wiki: ['names' => [], 'slug' => ''],
            status: 'approved',
        );
        self::assertStringContainsString('"wiki":{"names":{},"slug":""}', json_encode($readModel->toArray(), JSON_THROW_ON_ERROR));
    }
}
