<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Query\ListManagedImages;

use Source\Wiki\Image\Application\UseCase\Query\ListManagedImages\ListManagedImagesOutput;
use Source\Wiki\Image\Application\UseCase\Query\ManagedImageReadModel;
use Tests\TestCase;

class ListManagedImagesOutputTest extends TestCase
{
    public function testToArray(): void
    {
        $image = new ManagedImageReadModel(
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

        $output = new ListManagedImagesOutput();
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
