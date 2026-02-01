<?php

declare(strict_types=1);

namespace Tests\Wiki\ImageHideRequest\Application\UseCase\Command\RequestImageHide;

use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\ImageHideRequest\Application\UseCase\Command\RequestImageHide\RequestImageHideInput;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RequestImageHideInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $requesterName = 'Test Requester';
        $requesterEmail = 'requester@example.com';
        $reason = 'Privacy concern';

        $input = new RequestImageHideInput(
            $imageIdentifier,
            $requesterName,
            $requesterEmail,
            $reason,
        );

        $this->assertSame((string) $imageIdentifier, (string) $input->imageIdentifier());
        $this->assertSame($requesterName, $input->requesterName());
        $this->assertSame($requesterEmail, $input->requesterEmail());
        $this->assertSame($reason, $input->reason());
    }
}
