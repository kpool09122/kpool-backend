<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Query;

use Source\Wiki\Image\Application\UseCase\Query\DraftImageReadModel;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Tests\TestCase;

class DraftImageReadModelTest extends TestCase
{
    public function testToArray(): void
    {
        $readModel = new DraftImageReadModel(
            imageIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            publishedImageIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f102',
            url: 'https://example.com/images/talents/profile.jpg',
            resourceType: 'talent',
            wikiIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f201',
            imageUsage: 'profile',
            displayOrder: 1,
            sourceUrl: 'https://example.com/source',
            sourceName: 'Example Source',
            altText: 'Profile image',
            status: ApprovalStatus::UnderReview->value,
            uploadedAt: '2026-05-01T00:00:00+00:00',
        );

        $this->assertSame([
            'imageIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            'publishedImageIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f102',
            'url' => 'https://example.com/images/talents/profile.jpg',
            'resourceType' => 'talent',
            'wikiIdentifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f201',
            'imageUsage' => 'profile',
            'displayOrder' => 1,
            'sourceUrl' => 'https://example.com/source',
            'sourceName' => 'Example Source',
            'altText' => 'Profile image',
            'status' => ApprovalStatus::UnderReview->value,
            'uploadedAt' => '2026-05-01T00:00:00+00:00',
        ], $readModel->toArray());
    }
}
