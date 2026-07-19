<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\RequestImageDeletion;

use Source\Wiki\Image\Application\UseCase\Command\RequestImageDeletion\RequestImageDeletionInput;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RequestImageDeletionInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());

        $input = new RequestImageDeletionInput(
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
