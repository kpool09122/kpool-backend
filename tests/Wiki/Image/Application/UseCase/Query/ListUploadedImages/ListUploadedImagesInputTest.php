<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Query\ListUploadedImages;

use Source\Wiki\Image\Application\UseCase\Query\ListUploadedImages\ListUploadedImagesInput;
use Tests\TestCase;

class ListUploadedImagesInputTest extends TestCase
{
    public function testDefaults(): void
    {
        $input = new ListUploadedImagesInput(
            wikiIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
        );

        $this->assertSame(10, $input->perPage());
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f101', $input->wikiIdentifier());
    }

    public function testAccessors(): void
    {
        $input = new ListUploadedImagesInput(
            perPage: 20,
            wikiIdentifier: '01965bb2-bcc9-7c6f-8b90-89f7f217f102',
        );

        $this->assertSame(20, $input->perPage());
        $this->assertSame('01965bb2-bcc9-7c6f-8b90-89f7f217f102', $input->wikiIdentifier());
    }
}
