<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\ApproveImageDeletion;

use Source\Wiki\Image\Application\UseCase\Command\ApproveImageDeletion\ApproveImageDeletionInput;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ApproveImageDeletionInputTest extends TestCase
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

        $input = new ApproveImageDeletionInput(
            $imageIdentifier,
            $principalIdentifier,
        );

        $this->assertSame((string) $imageIdentifier, (string) $input->imageIdentifier());
        $this->assertSame((string) $principalIdentifier, (string) $input->principalIdentifier());
    }
}
