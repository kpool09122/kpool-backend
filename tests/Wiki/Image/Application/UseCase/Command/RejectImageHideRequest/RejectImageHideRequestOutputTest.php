<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\RejectImageHideRequest;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Application\UseCase\Command\RejectImageHideRequest\RejectImageHideRequestOutput;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\ValueObject\HideRequest;
use Source\Wiki\Image\Domain\ValueObject\ImageHideRequestStatus;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectImageHideRequestOutputTest extends TestCase
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

        $hideRequest = new HideRequest(
            'Test Requester',
            'requester@example.com',
            'Privacy concern',
            ImageHideRequestStatus::REJECTED,
            new DateTimeImmutable(),
            $reviewerIdentifier,
            new DateTimeImmutable(),
            'Not applicable',
        );

        $image = new Image(
            $imageIdentifier,
            ResourceType::TALENT,
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new ImagePath('images/test.png'),
            ImageUsage::PROFILE,
            1,
            'https://example.com/source',
            'Example Source',
            'Alt text',
            false,
            null,
            null,
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new DateTimeImmutable(),
            null,
            null,
            null,
            null,
            [$hideRequest],
        );

        $output = new RejectImageHideRequestOutput();
        $output->setImage($image);

        $result = $output->toArray();

        $this->assertSame((string) $imageIdentifier, $result['imageIdentifier']);
        $this->assertSame('rejected', $result['status']);
        $this->assertSame('Not applicable', $result['reviewerComment']);
        $this->assertFalse($result['isHidden']);
    }

    /**
     * 正常系: Imageがセットされていない場合、toArrayが全てnullの配列を返すこと.
     *
     * @return void
     */
    public function testToArrayWithoutImage(): void
    {
        $output = new RejectImageHideRequestOutput();

        $result = $output->toArray();

        $this->assertSame([
            'imageIdentifier' => null,
            'status' => null,
            'reviewerComment' => null,
            'isHidden' => null,
        ], $result);
    }
}
