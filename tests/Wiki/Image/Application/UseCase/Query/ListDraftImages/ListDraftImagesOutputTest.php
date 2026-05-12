<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Query\ListDraftImages;

use Source\Wiki\Image\Application\UseCase\Query\DraftImageReadModel;
use Source\Wiki\Image\Application\UseCase\Query\ListDraftImages\ListDraftImagesOutput;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Tests\TestCase;

class ListDraftImagesOutputTest extends TestCase
{
    public function testToArray(): void
    {
        $image = new DraftImageReadModel(
            imageIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            publishedImageIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f102',
            url: 'https://example.com/images/talents/profile.jpg',
            resourceType: 'talent',
            translationSetIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f201',
            imageUsage: 'profile',
            displayOrder: 1,
            sourceUrl: 'https://example.com/source',
            sourceName: 'Example Source',
            altText: 'Profile image',
            status: ApprovalStatus::Pending->value,
            uploadedAt: '2026-05-01T00:00:00+00:00',
        );

        $output = new ListDraftImagesOutput();
        $output->output([$image], 1, 3, 5, 2);

        $this->assertSame([
            'images' => [$image->toArray()],
            'current_page' => 1,
            'last_page' => 3,
            'total' => 5,
            'per_page' => 2,
        ], $output->toArray());
    }
}
