<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Query;

use Source\Wiki\Image\Application\UseCase\Query\ImageDeletionRequestListItemReadModel;
use Tests\TestCase;

class ImageDeletionRequestListItemReadModelTest extends TestCase
{
    public function testToArray(): void
    {
        $readModel = new ImageDeletionRequestListItemReadModel(
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
            name: '申請者',
            email: 'requester@example.com',
            reason: '権利者から削除依頼があったため',
        );

        $this->assertSame([
            'imageIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            'url' => 'https://example.com/images/talents/profile.jpg',
            'resourceType' => 'talent',
            'translationSetIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f201',
            'displayOrder' => 1,
            'sourceUrl' => 'https://example.com/source',
            'sourceName' => 'Example Source',
            'altText' => 'Profile image',
            'isHidden' => false,
            'uploadedAt' => '2026-05-01T00:00:00+00:00',
            'name' => '申請者',
            'email' => 'requester@example.com',
            'reason' => '権利者から削除依頼があったため',
        ], $readModel->toArray());
    }
}
