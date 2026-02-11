<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\RejectImage;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Application\UseCase\Command\RejectImage\RejectImage;
use Source\Wiki\Image\Application\UseCase\Command\RejectImage\RejectImageInput;
use Source\Wiki\Image\Application\UseCase\Command\RejectImage\RejectImageInterface;
use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Image\Domain\Repository\DraftImageRepositoryInterface;
use Source\Wiki\Image\Domain\Service\ImageAuthorizationResourceBuilderInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
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
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

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
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcess(): void
    {
        $testData = $this->createTestData();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
        $resource = new Resource(type: ResourceType::IMAGE);

        $input = new RejectImageInput($testData->draftImageIdentifier, $principalIdentifier);

        $draftImageRepository = Mockery::mock(DraftImageRepositoryInterface::class);
        $draftImageRepository->shouldReceive('findById')
            ->once()
            ->with($testData->draftImageIdentifier)
            ->andReturn($testData->draftImage);
        $draftImageRepository->shouldReceive('save')
            ->once()
            ->andReturn(null);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($principalIdentifier)
            ->andReturn($principal);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(true);

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);
        $imageAuthorizationResourceBuilder->shouldReceive('buildFromDraftImage')
            ->once()
            ->with($testData->draftImage)
            ->andReturn($resource);

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

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
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessDraftImageNotFound(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
        $input = new RejectImageInput($imageIdentifier, $principalIdentifier);

        $draftImageRepository = Mockery::mock(DraftImageRepositoryInterface::class);
        $draftImageRepository->shouldReceive('findById')
            ->once()
            ->with($imageIdentifier)
            ->andReturn(null);
        $draftImageRepository->shouldNotReceive('save');

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($principalIdentifier)
            ->andReturn($principal);

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);
        $imageAuthorizationResourceBuilder->shouldNotReceive('buildFromDraftImage');

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

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
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function testProcessInvalidStatus(): void
    {
        $testData = $this->createTestDataWithInvalidStatus();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
        $resource = new Resource(type: ResourceType::IMAGE);
        $input = new RejectImageInput($testData->draftImageIdentifier, $principalIdentifier);

        $draftImageRepository = Mockery::mock(DraftImageRepositoryInterface::class);
        $draftImageRepository->shouldReceive('findById')
            ->once()
            ->with($testData->draftImageIdentifier)
            ->andReturn($testData->draftImage);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($principalIdentifier)
            ->andReturn($principal);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(true);

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);
        $imageAuthorizationResourceBuilder->shouldReceive('buildFromDraftImage')
            ->once()
            ->with($testData->draftImage)
            ->andReturn($resource);

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $this->expectException(InvalidStatusException::class);
        $rejectImage = $this->app->make(RejectImageInterface::class);
        $rejectImage->process($input);
    }

    /**
     * 異常系：権限がない場合、DisallowedExceptionがスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ImageNotFoundException
     * @throws InvalidStatusException
     * @throws PrincipalNotFoundException
     */
    public function testProcessDisallowed(): void
    {
        $testData = $this->createTestData();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
        $resource = new Resource(type: ResourceType::IMAGE);

        $input = new RejectImageInput($testData->draftImageIdentifier, $principalIdentifier);

        $draftImageRepository = Mockery::mock(DraftImageRepositoryInterface::class);
        $draftImageRepository->shouldReceive('findById')
            ->once()
            ->with($testData->draftImageIdentifier)
            ->andReturn($testData->draftImage);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($principalIdentifier)
            ->andReturn($principal);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(false);

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);
        $imageAuthorizationResourceBuilder->shouldReceive('buildFromDraftImage')
            ->once()
            ->with($testData->draftImage)
            ->andReturn($resource);

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $this->expectException(DisallowedException::class);
        $rejectImage = $this->app->make(RejectImageInterface::class);
        $rejectImage->process($input);
    }

    /**
     * 異常系：Principalが見つからない場合、PrincipalNotFoundExceptionがスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws ImageNotFoundException
     * @throws InvalidStatusException
     * @throws DisallowedException
     */
    public function testProcessPrincipalNotFound(): void
    {
        $imageIdentifier = new ImageIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $input = new RejectImageInput($imageIdentifier, $principalIdentifier);

        $draftImageRepository = Mockery::mock(DraftImageRepositoryInterface::class);
        $draftImageRepository->shouldNotReceive('findById');

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($principalIdentifier)
            ->andReturn(null);

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);
        $imageAuthorizationResourceBuilder->shouldNotReceive('buildFromDraftImage');

        $this->app->instance(DraftImageRepositoryInterface::class, $draftImageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $this->expectException(PrincipalNotFoundException::class);
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
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $imagePath = new ImagePath('images/test.png');
        $imageUsage = ImageUsage::PROFILE;
        $displayOrder = 1;
        $sourceUrl = 'https://example.com/source';
        $sourceName = 'Example Source';
        $altText = 'Profile image of talent';
        $agreedToTermsAt = new DateTimeImmutable('2024-01-01 00:00:00');
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $uploadedAt = new DateTimeImmutable();

        $draftImage = new DraftImage(
            $draftImageIdentifier,
            null,
            $resourceType,
            $wikiIdentifier,
            $principalIdentifier,
            $imagePath,
            $imageUsage,
            $displayOrder,
            $sourceUrl,
            $sourceName,
            $altText,
            ApprovalStatus::UnderReview,
            $agreedToTermsAt,
            $uploadedAt,
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
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $imagePath = new ImagePath('images/test.png');
        $imageUsage = ImageUsage::PROFILE;
        $displayOrder = 1;
        $sourceUrl = 'https://example.com/source';
        $sourceName = 'Example Source';
        $altText = 'Profile image of talent';
        $agreedToTermsAt = new DateTimeImmutable('2024-01-01 00:00:00');
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $uploadedAt = new DateTimeImmutable();

        $draftImage = new DraftImage(
            $draftImageIdentifier,
            null,
            $resourceType,
            $wikiIdentifier,
            $principalIdentifier,
            $imagePath,
            $imageUsage,
            $displayOrder,
            $sourceUrl,
            $sourceName,
            $altText,
            ApprovalStatus::Pending, // UnderReviewではない
            $agreedToTermsAt,
            $uploadedAt,
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
