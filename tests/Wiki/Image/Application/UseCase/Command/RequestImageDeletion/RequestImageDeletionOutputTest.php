<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\RequestImageDeletion;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Image\Application\UseCase\Command\RequestImageDeletion\RequestImageDeletionOutput;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\ValueObject\RightsConfirmationAgreed;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RequestImageDeletionOutputTest extends TestCase
{
    /**
     * 正常系: Imageがセットされている場合、toArrayが正しい値を返すこと.
     *
     * @return void
     */
    public function testToArrayWithImage(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $image = new Image(
            $imageIdentifier,
            ResourceType::TALENT,
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new ImagePath('images/test.png'),
            1,
            'https://example.com/source',
            'Example Source',
            'Alt text',
            false,
            null,
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new DateTimeImmutable(),
            null,
            null,
            null,
            null,
            new RightsConfirmationAgreed(true),
        );

        $image->requestDeletion('Test Requester', 'requester@example.com', 'Privacy concern');

        $output = new RequestImageDeletionOutput();
        $output->setImage($image);

        $result = $output->toArray();

        $this->assertSame((string) $imageIdentifier, $result['imageIdentifier']);
        $this->assertSame('Test Requester', $result['requesterName']);
        $this->assertSame('requester@example.com', $result['requesterEmail']);
        $this->assertSame('Privacy concern', $result['reason']);
        $this->assertTrue($result['isHidden']);
    }

    /**
     * 正常系: Imageがセットされていない場合、toArrayが全てnullの配列を返すこと.
     *
     * @return void
     */
    public function testToArrayWithoutImage(): void
    {
        $output = new RequestImageDeletionOutput();

        $result = $output->toArray();

        $this->assertSame([
            'imageIdentifier' => null,
            'requesterName' => null,
            'requesterEmail' => null,
            'reason' => null,
            'isHidden' => null,
        ], $result);
    }
}
