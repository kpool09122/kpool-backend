<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Query;

use Source\Wiki\Image\Application\UseCase\Query\UploadedImageReadModel;
use Tests\TestCase;

class UploadedImageReadModelTest extends TestCase
{
    public function testToArray(): void
    {
        $readModel = new UploadedImageReadModel(
            imageIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            url: 'https://example.com/images/talents/profile.jpg',
            resourceType: 'talent',
            translationSetIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f201',
            imageUsage: 'profile',
            displayOrder: 1,
            sourceUrl: 'https://example.com/source',
            sourceName: 'Example Source',
            altText: 'Profile image',
            isHidden: false,
            uploadedAt: '2026-05-01T00:00:00+00:00',
        );

        $this->assertSame([
            'imageIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            'url' => 'https://example.com/images/talents/profile.jpg',
            'resourceType' => 'talent',
            'translationSetIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f201',
            'imageUsage' => 'profile',
            'displayOrder' => 1,
            'sourceUrl' => 'https://example.com/source',
            'sourceName' => 'Example Source',
            'altText' => 'Profile image',
            'isHidden' => false,
            'uploadedAt' => '2026-05-01T00:00:00+00:00',
        ], $readModel->toArray());
    }
}
