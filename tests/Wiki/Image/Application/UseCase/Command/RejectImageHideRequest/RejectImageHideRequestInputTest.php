<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\RejectImageHideRequest;

use Source\Wiki\Image\Application\UseCase\Command\RejectImageHideRequest\RejectImageHideRequestInput;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectImageHideRequestInputTest extends TestCase
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

        $input = new RejectImageHideRequestInput(
            $imageIdentifier,
            $principalIdentifier,
            'Not applicable',
        );

        $this->assertSame((string) $imageIdentifier, (string) $input->imageIdentifier());
        $this->assertSame((string) $principalIdentifier, (string) $input->principalIdentifier());
        $this->assertSame('Not applicable', $input->reviewerComment());
    }
}
