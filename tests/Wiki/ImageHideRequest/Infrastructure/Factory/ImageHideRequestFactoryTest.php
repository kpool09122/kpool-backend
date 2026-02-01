<?php

declare(strict_types=1);

namespace Tests\Wiki\ImageHideRequest\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\ImageHideRequest\Domain\Factory\ImageHideRequestFactoryInterface;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestStatus;
use Source\Wiki\ImageHideRequest\Infrastructure\Factory\ImageHideRequestFactory;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ImageHideRequestFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $imageHideRequestFactory = $this->app->make(ImageHideRequestFactoryInterface::class);
        $this->assertInstanceOf(ImageHideRequestFactory::class, $imageHideRequestFactory);
    }

    /**
     * 正常系: ImageHideRequest Entityが正しく作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $requesterName = 'Test Requester';
        $requesterEmail = 'requester@example.com';
        $reason = 'Privacy concern';

        $imageHideRequestFactory = $this->app->make(ImageHideRequestFactoryInterface::class);
        $imageHideRequest = $imageHideRequestFactory->create(
            $imageIdentifier,
            $requesterName,
            $requesterEmail,
            $reason,
        );

        $this->assertTrue(UuidValidator::isValid((string) $imageHideRequest->requestIdentifier()));
        $this->assertSame((string) $imageIdentifier, (string) $imageHideRequest->imageIdentifier());
        $this->assertSame($requesterName, $imageHideRequest->requesterName());
        $this->assertSame($requesterEmail, $imageHideRequest->requesterEmail());
        $this->assertSame($reason, $imageHideRequest->reason());
        $this->assertSame(ImageHideRequestStatus::PENDING, $imageHideRequest->status());
        $this->assertNotNull($imageHideRequest->requestedAt());
        $this->assertNull($imageHideRequest->reviewerIdentifier());
        $this->assertNull($imageHideRequest->reviewedAt());
        $this->assertNull($imageHideRequest->reviewerComment());
    }
}
