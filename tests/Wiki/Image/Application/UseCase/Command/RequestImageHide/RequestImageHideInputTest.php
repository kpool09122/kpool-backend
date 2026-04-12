<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\RequestImageHide;

use Source\Wiki\Image\Application\UseCase\Command\RequestImageHide\RequestImageHideInput;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RequestImageHideInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());

        $input = new RequestImageHideInput(
            $imageIdentifier,
            'Test Requester',
            'requester@example.com',
            'Privacy concern',
        );

        $this->assertSame((string) $imageIdentifier, (string) $input->imageIdentifier());
        $this->assertSame('Test Requester', $input->requesterName());
        $this->assertSame('requester@example.com', $input->requesterEmail());
        $this->assertSame('Privacy concern', $input->reason());
    }
}
