<?php

declare(strict_types=1);

namespace Tests\Wiki\ImageHideRequest\Application\UseCase\Command\RejectImageHideRequest;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Wiki\ImageHideRequest\Application\UseCase\Command\RejectImageHideRequest\RejectImageHideRequestOutput;
use Source\Wiki\ImageHideRequest\Application\UseCase\Command\RejectImageHideRequest\RejectImageHideRequestOutputPort;
use Source\Wiki\ImageHideRequest\Domain\Entity\ImageHideRequest;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestIdentifier;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestStatus;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class RejectImageHideRequestOutputTest extends TestCase
{
    /**
     * 正常系: OutputPortインターフェースを実装していること.
     */
    public function testImplementsOutputPort(): void
    {
        $output = new RejectImageHideRequestOutput();
        $this->assertInstanceOf(RejectImageHideRequestOutputPort::class, $output);
    }

    /**
     * 正常系: エンティティ未設定時にnullフィールドを返すこと.
     */
    public function testToArrayReturnsNullFieldsWhenNoEntitySet(): void
    {
        $output = new RejectImageHideRequestOutput();
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
        $reviewerComment = 'Rejected for invalid reason.';

        $imageHideRequest = new ImageHideRequest(
            $requestIdentifier,
            $imageIdentifier,
            'Test Requester',
            'requester@example.com',
            'Privacy concern',
            ImageHideRequestStatus::REJECTED,
            new DateTimeImmutable(),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new DateTimeImmutable(),
            $reviewerComment,
        );

        $output = new RejectImageHideRequestOutput();
        $output->setImageHideRequest($imageHideRequest);
        $result = $output->toArray();

        $this->assertSame((string) $requestIdentifier, $result['requestIdentifier']);
        $this->assertSame((string) $imageIdentifier, $result['imageIdentifier']);
        $this->assertSame('rejected', $result['status']);
        $this->assertSame($reviewerComment, $result['reviewerComment']);
    }
}
