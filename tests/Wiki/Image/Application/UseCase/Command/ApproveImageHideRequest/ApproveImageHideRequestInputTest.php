<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\ApproveImageHideRequest;

use Source\Wiki\Image\Application\UseCase\Command\ApproveImageHideRequest\ApproveImageHideRequestInput;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ApproveImageHideRequestInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new ApproveImageHideRequestInput(
            $imageIdentifier,
            $principalIdentifier,
            'Approved for privacy',
        );

        $this->assertSame((string) $imageIdentifier, (string) $input->imageIdentifier());
        $this->assertSame((string) $principalIdentifier, (string) $input->principalIdentifier());
        $this->assertSame('Approved for privacy', $input->reviewerComment());
    }
}
