<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\RejectImage;

use Source\Wiki\Image\Application\UseCase\Command\RejectImage\RejectImageInput;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectImageInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new RejectImageInput(
            $imageIdentifier,
            $principalIdentifier,
        );

        $this->assertSame((string) $imageIdentifier, (string) $input->imageIdentifier());
        $this->assertSame($principalIdentifier, $input->principalIdentifier());
    }
}
