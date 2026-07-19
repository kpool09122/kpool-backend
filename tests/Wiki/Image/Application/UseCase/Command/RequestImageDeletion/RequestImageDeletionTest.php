<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\RequestImageDeletion;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Application\UseCase\Command\RequestImageDeletion\RequestImageDeletion;
use Source\Wiki\Image\Application\UseCase\Command\RequestImageDeletion\RequestImageDeletionInput;
use Source\Wiki\Image\Application\UseCase\Command\RequestImageDeletion\RequestImageDeletionInterface;
use Source\Wiki\Image\Application\UseCase\Command\RequestImageDeletion\RequestImageDeletionOutput;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Exception\ImageDeletionRequestAlreadyPendingException;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Image\Domain\ValueObject\DeletionRequest;
use Source\Wiki\Image\Domain\ValueObject\RightsConfirmationAgreed;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RequestImageDeletionTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);

        $requestImageDeletion = $this->app->make(RequestImageDeletionInterface::class);
        $this->assertInstanceOf(RequestImageDeletion::class, $requestImageDeletion);
    }

    /**
     * 正常系：正しくdeletionRequestが作成されること.
     *
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $image = $this->createTestImage();
        $imageIdentifier = $image->imageIdentifier();

        $input = new RequestImageDeletionInput(
            $imageIdentifier,
            'Test Requester',
            'requester@example.com',
            'Privacy concern',
        );

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageRepository->shouldReceive('findById')
            ->once()
            ->with($imageIdentifier)
            ->andReturn($image);
        $imageRepository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);

        $requestImageDeletion = $this->app->make(RequestImageDeletionInterface::class);
        $output = new RequestImageDeletionOutput();
        $requestImageDeletion->process($input, $output);

        $result = $output->toArray();
        $this->assertSame((string) $imageIdentifier, $result['imageIdentifier']);
        $this->assertTrue($result['isHidden']);
    }

    /**
     * 異常系：Imageが見つからない場合、ImageNotFoundExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessImageNotFound(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $input = new RequestImageDeletionInput(
            $imageIdentifier,
            'Test Requester',
            'requester@example.com',
            'Privacy concern',
        );

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageRepository->shouldReceive('findById')
            ->once()
            ->with($imageIdentifier)
            ->andReturn(null);
        $imageRepository->shouldNotReceive('save');

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);

        $this->expectException(ImageNotFoundException::class);
        $requestImageDeletion = $this->app->make(RequestImageDeletionInterface::class);
        $output = new RequestImageDeletionOutput();
        $requestImageDeletion->process($input, $output);
    }

    /**
     * 異常系：既にpendingのdeletionRequestがある場合、ImageDeletionRequestAlreadyPendingExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessAlreadyPending(): void
    {
        $image = $this->createTestImageWithPendingDeletionRequest();
        $imageIdentifier = $image->imageIdentifier();

        $input = new RequestImageDeletionInput(
            $imageIdentifier,
            'Test Requester',
            'requester@example.com',
            'Privacy concern',
        );

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageRepository->shouldReceive('findById')
            ->once()
            ->with($imageIdentifier)
            ->andReturn($image);
        $imageRepository->shouldNotReceive('save');

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);

        $this->expectException(ImageDeletionRequestAlreadyPendingException::class);
        $requestImageDeletion = $this->app->make(RequestImageDeletionInterface::class);
        $output = new RequestImageDeletionOutput();
        $requestImageDeletion->process($input, $output);
    }

    private function createTestImage(): Image
    {
        return new Image(
            new ImageIdentifier(StrTestHelper::generateUuid()),
            ResourceType::TALENT,
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new ImagePath('images/test.png'),
            1,
            'https://example.com/source',
            'Example Source',
            'Profile image of talent',
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
    }

    private function createTestImageWithPendingDeletionRequest(): Image
    {
        $deletionRequest = new DeletionRequest(
            'Existing Requester',
            'existing@example.com',
            'Existing reason',
            new DateTimeImmutable(),
            null,
            null,
            null,
        );

        return new Image(
            new ImageIdentifier(StrTestHelper::generateUuid()),
            ResourceType::TALENT,
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new ImagePath('images/test.png'),
            1,
            'https://example.com/source',
            'Example Source',
            'Profile image of talent',
            true,
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
    }
}
