<?php

declare(strict_types=1);

namespace Tests\Wiki\ImageHideRequest\Application\UseCase\Command\RequestImageHide;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Wiki\ImageHideRequest\Application\UseCase\Command\RequestImageHide\RequestImageHideOutput;
use Source\Wiki\ImageHideRequest\Application\UseCase\Command\RequestImageHide\RequestImageHideOutputPort;
use Source\Wiki\ImageHideRequest\Domain\Entity\ImageHideRequest;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestIdentifier;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestStatus;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Tests\Helper\StrTestHelper;

class RequestImageHideOutputTest extends TestCase
{
    /**
     * 正常系: OutputPortインターフェースを実装していること.
     */
    public function testImplementsOutputPort(): void
    {
        $output = new RequestImageHideOutput();
        $this->assertInstanceOf(RequestImageHideOutputPort::class, $output);
    }

    /**
     * 正常系: エンティティ未設定時にnullフィールドを返すこと.
     */
    public function testToArrayReturnsNullFieldsWhenNoEntitySet(): void
    {
        $output = new RequestImageHideOutput();
        $result = $output->toArray();

        $this->assertNull($result['requestIdentifier']);
        $this->assertNull($result['imageIdentifier']);
        $this->assertNull($result['requesterName']);
        $this->assertNull($result['requesterEmail']);
        $this->assertNull($result['reason']);
        $this->assertNull($result['status']);
    }

    /**
     * 正常系: エンティティ設定時に正しい値を返すこと.
     */
    public function testToArrayReturnsCorrectValuesWhenEntitySet(): void
    {
        $requestIdentifier = new ImageHideRequestIdentifier(StrTestHelper::generateUuid());
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $requesterName = 'Test Requester';
        $requesterEmail = 'requester@example.com';
        $reason = 'Privacy concern';

        $imageHideRequest = new ImageHideRequest(
            $requestIdentifier,
            $imageIdentifier,
            $requesterName,
            $requesterEmail,
            $reason,
            ImageHideRequestStatus::PENDING,
            new DateTimeImmutable(),
            null,
            null,
            null,
        );

        $output = new RequestImageHideOutput();
        $output->setImageHideRequest($imageHideRequest);
        $result = $output->toArray();

        $this->assertSame((string) $requestIdentifier, $result['requestIdentifier']);
        $this->assertSame((string) $imageIdentifier, $result['imageIdentifier']);
        $this->assertSame($requesterName, $result['requesterName']);
        $this->assertSame($requesterEmail, $result['requesterEmail']);
        $this->assertSame($reason, $result['reason']);
        $this->assertSame('pending', $result['status']);
    }
}
