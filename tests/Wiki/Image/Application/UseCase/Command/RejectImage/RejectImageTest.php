<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\RejectImage;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Application\UseCase\Command\RejectImage\RejectImage;
use Source\Wiki\Image\Application\UseCase\Command\RejectImage\RejectImageInput;
use Source\Wiki\Image\Application\UseCase\Command\RejectImage\RejectImageInterface;
use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Image\Domain\Repository\DraftImageRepositoryInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectImageTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     * @return void
     */
    public function test__construct(): void
    {
        $draftImageRepository = Mockery::mock(DraftImageRepositoryInterface::class);
        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);

        $rejectImage = $this->app->make(RejectImageInterface::class);
        $this->assertInstanceOf(RejectImage::class, $rejectImage);
    }

    /**
     * 正常系：正しくDraftImageがRejectedステータスに変更されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ImageNotFoundException
     * @throws InvalidStatusException
     */
    public function testProcess(): void
    {
        $testData = $this->createTestData();

        $input = new RejectImageInput($testData->draftImageIdentifier);

        $draftImageRepository = Mockery::mock(DraftImageRepositoryInterface::class);
        $draftImageRepository->shouldReceive('findById')
            ->once()
            ->with($testData->draftImageIdentifier)
            ->andReturn($testData->draftImage);
        $draftImageRepository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);

        $rejectImage = $this->app->make(RejectImageInterface::class);
        $result = $rejectImage->process($input);

        $this->assertSame(ApprovalStatus::Rejected, $result->status());
    }

    /**
     * 異常系：DraftImageが見つからない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws InvalidStatusException
     */
    public function testProcessDraftImageNotFound(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $input = new RejectImageInput($imageIdentifier);

        $draftImageRepository = Mockery::mock(DraftImageRepositoryInterface::class);
        $draftImageRepository->shouldReceive('findById')
            ->once()
            ->with($imageIdentifier)
            ->andReturn(null);

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);

        $this->expectException(ImageNotFoundException::class);
        $rejectImage = $this->app->make(RejectImageInterface::class);
        $rejectImage->process($input);
    }

    /**
     * 異常系：ステータスがUnderReviewでない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ImageNotFoundException
     */
    public function testProcessInvalidStatus(): void
    {
        $testData = $this->createTestDataWithInvalidStatus();
        $input = new RejectImageInput($testData->draftImageIdentifier);

        $draftImageRepository = Mockery::mock(DraftImageRepositoryInterface::class);
        $draftImageRepository->shouldReceive('findById')
            ->once()
            ->with($testData->draftImageIdentifier)
            ->andReturn($testData->draftImage);

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);

        $this->expectException(InvalidStatusException::class);
        $rejectImage = $this->app->make(RejectImageInterface::class);
        $rejectImage->process($input);
    }

    /**
     * @return RejectImageTestData
     */
    private function createTestData(): RejectImageTestData
    {
        $draftImageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::TALENT;
        $resourceIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());
        $imagePath = new ImagePath('images/test.png');
        $imageUsage = ImageUsage::PROFILE;
        $displayOrder = 1;
        $sourceUrl = 'https://example.com/source';
        $sourceName = 'Example Source';
        $altText = 'Profile image of talent';
        $agreedToTermsAt = new DateTimeImmutable('2024-01-01 00:00:00');
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $createdAt = new DateTimeImmutable();

        $draftImage = new DraftImage(
            $draftImageIdentifier,
            null,
            $resourceType,
            $resourceIdentifier,
            $principalIdentifier,
            $imagePath,
            $imageUsage,
            $displayOrder,
            $sourceUrl,
            $sourceName,
            $altText,
            ApprovalStatus::UnderReview,
            $agreedToTermsAt,
            $createdAt,
        );

        return new RejectImageTestData(
            $draftImageIdentifier,
            $draftImage,
        );
    }

    /**
     * @return RejectImageTestData
     */
    private function createTestDataWithInvalidStatus(): RejectImageTestData
    {
        $draftImageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::TALENT;
        $resourceIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());
        $imagePath = new ImagePath('images/test.png');
        $imageUsage = ImageUsage::PROFILE;
        $displayOrder = 1;
        $sourceUrl = 'https://example.com/source';
        $sourceName = 'Example Source';
        $altText = 'Profile image of talent';
        $agreedToTermsAt = new DateTimeImmutable('2024-01-01 00:00:00');
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $createdAt = new DateTimeImmutable();

        $draftImage = new DraftImage(
            $draftImageIdentifier,
            null,
            $resourceType,
            $resourceIdentifier,
            $principalIdentifier,
            $imagePath,
            $imageUsage,
            $displayOrder,
            $sourceUrl,
            $sourceName,
            $altText,
            ApprovalStatus::Pending, // UnderReviewではない
            $agreedToTermsAt,
            $createdAt,
        );

        return new RejectImageTestData(
            $draftImageIdentifier,
            $draftImage,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class RejectImageTestData
{
    public function __construct(
        public ImageIdentifier $draftImageIdentifier,
        public DraftImage $draftImage,
    ) {
    }
}
