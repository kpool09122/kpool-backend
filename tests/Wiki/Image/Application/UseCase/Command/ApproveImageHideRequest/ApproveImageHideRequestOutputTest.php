<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\ApproveImageHideRequest;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Application\UseCase\Command\ApproveImageHideRequest\ApproveImageHideRequestOutput;
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

class ApproveImageHideRequestOutputTest extends TestCase
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
            ImageHideRequestStatus::APPROVED,
            new DateTimeImmutable(),
            $reviewerIdentifier,
            new DateTimeImmutable(),
            'Approved for privacy',
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
            true,
            $reviewerIdentifier,
            new DateTimeImmutable(),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new DateTimeImmutable(),
            null,
            null,
            null,
            null,
            [$hideRequest],
        );

        $output = new ApproveImageHideRequestOutput();
        $output->setImage($image);

        $result = $output->toArray();

        $this->assertSame((string) $imageIdentifier, $result['imageIdentifier']);
        $this->assertSame('approved', $result['status']);
        $this->assertSame('Approved for privacy', $result['reviewerComment']);
        $this->assertTrue($result['isHidden']);
    }

    /**
     * 正常系: Imageがセットされていない場合、toArrayが全てnullの配列を返すこと.
     *
     * @return void
     */
    public function testToArrayWithoutImage(): void
    {
        $output = new ApproveImageHideRequestOutput();

        $result = $output->toArray();

        $this->assertSame([
            'imageIdentifier' => null,
            'status' => null,
            'reviewerComment' => null,
            'isHidden' => null,
        ], $result);
    }
}
