<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\RejectImageDeletion;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Image\Application\UseCase\Command\RejectImageDeletion\RejectImageDeletionOutput;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\ValueObject\DeletionRequest;
use Source\Wiki\Image\Domain\ValueObject\RightsConfirmationAgreed;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectImageDeletionOutputTest extends TestCase
{
    /**
     * 正常系: Imageがセットされている場合、toArrayが正しい値を返すこと.
     *
     * @return void
     */
    public function testToArrayWithImage(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $reviewerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $deletionRequest = new DeletionRequest(
            'Test Requester',
            'requester@example.com',
            'Privacy concern',
            new DateTimeImmutable(),
            $reviewerIdentifier,
            new DateTimeImmutable(),
            'Not applicable',
        );

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
            [$deletionRequest],
        );

        $output = new RejectImageDeletionOutput();
        $output->setImage($image);

        $result = $output->toArray();

        $this->assertSame((string) $imageIdentifier, $result['imageIdentifier']);
        $this->assertSame('Not applicable', $result['rejectReason']);
        $this->assertFalse($result['isHidden']);
    }

    /**
     * 正常系: Imageがセットされていない場合、toArrayが全てnullの配列を返すこと.
     *
     * @return void
     */
    public function testToArrayWithoutImage(): void
    {
        $output = new RejectImageDeletionOutput();

        $result = $output->toArray();

        $this->assertSame([
            'imageIdentifier' => null,
            'rejectReason' => null,
            'isHidden' => null,
        ], $result);
    }
}
