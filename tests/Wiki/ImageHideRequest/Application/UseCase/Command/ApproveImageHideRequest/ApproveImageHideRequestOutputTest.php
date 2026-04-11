<?php

declare(strict_types=1);

namespace Tests\Wiki\ImageHideRequest\Application\UseCase\Command\ApproveImageHideRequest;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Wiki\ImageHideRequest\Application\UseCase\Command\ApproveImageHideRequest\ApproveImageHideRequestOutput;
use Source\Wiki\ImageHideRequest\Application\UseCase\Command\ApproveImageHideRequest\ApproveImageHideRequestOutputPort;
use Source\Wiki\ImageHideRequest\Domain\Entity\ImageHideRequest;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestIdentifier;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestStatus;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class ApproveImageHideRequestOutputTest extends TestCase
{
    /**
     * 正常系: OutputPortインターフェースを実装していること.
     */
    public function testImplementsOutputPort(): void
    {
        $output = new ApproveImageHideRequestOutput();
        $this->assertInstanceOf(ApproveImageHideRequestOutputPort::class, $output);
    }

    /**
     * 正常系: エンティティ未設定時にnullフィールドを返すこと.
     */
    public function testToArrayReturnsNullFieldsWhenNoEntitySet(): void
    {
        $output = new ApproveImageHideRequestOutput();
        $result = $output->toArray();

        $this->assertNull($result['requestIdentifier']);
        $this->assertNull($result['imageIdentifier']);
        $this->assertNull($result['status']);
        $this->assertNull($result['reviewerComment']);
    }

    /**
     * 正常系: エンティティ設定時に正しい値を返すこと.
     */
    public function testToArrayReturnsCorrectValuesWhenEntitySet(): void
    {
        $requestIdentifier = new ImageHideRequestIdentifier(StrTestHelper::generateUuid());
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $reviewerComment = 'Approved for valid reason.';

        $imageHideRequest = new ImageHideRequest(
            $requestIdentifier,
            $imageIdentifier,
            'Test Requester',
            'requester@example.com',
            'Privacy concern',
            ImageHideRequestStatus::APPROVED,
            new DateTimeImmutable(),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new DateTimeImmutable(),
            $reviewerComment,
        );

        $output = new ApproveImageHideRequestOutput();
        $output->setImageHideRequest($imageHideRequest);
        $result = $output->toArray();

        $this->assertSame((string) $requestIdentifier, $result['requestIdentifier']);
        $this->assertSame((string) $imageIdentifier, $result['imageIdentifier']);
        $this->assertSame('approved', $result['status']);
        $this->assertSame($reviewerComment, $result['reviewerComment']);
    }
}
