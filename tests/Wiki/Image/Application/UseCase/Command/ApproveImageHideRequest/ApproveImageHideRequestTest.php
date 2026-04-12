<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\ApproveImageHideRequest;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Application\UseCase\Command\ApproveImageHideRequest\ApproveImageHideRequest;
use Source\Wiki\Image\Application\UseCase\Command\ApproveImageHideRequest\ApproveImageHideRequestInput;
use Source\Wiki\Image\Application\UseCase\Command\ApproveImageHideRequest\ApproveImageHideRequestInterface;
use Source\Wiki\Image\Application\UseCase\Command\ApproveImageHideRequest\ApproveImageHideRequestOutput;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Exception\ImageHideRequestNotPendingException;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Image\Domain\Service\ImageAuthorizationResourceBuilderInterface;
use Source\Wiki\Image\Domain\ValueObject\HideRequest;
use Source\Wiki\Image\Domain\ValueObject\ImageHideRequestStatus;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ApproveImageHideRequestTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $approveImageHideRequest = $this->app->make(ApproveImageHideRequestInterface::class);
        $this->assertInstanceOf(ApproveImageHideRequest::class, $approveImageHideRequest);
    }

    /**
     * 正常系：正しくhideRequestが承認されること.
     *
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $image = $this->createTestImageWithPendingHideRequest();
        $imageIdentifier = $image->imageIdentifier();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
        $resource = new Resource(type: ResourceType::IMAGE);

        $input = new ApproveImageHideRequestInput($imageIdentifier, $principalIdentifier, 'Approved for privacy');

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageRepository->shouldReceive('findById')
            ->once()
            ->with($imageIdentifier)
            ->andReturn($image);
        $imageRepository->shouldReceive('save')
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
        $imageAuthorizationResourceBuilder->shouldReceive('buildFromImage')
            ->once()
            ->with($image)
            ->andReturn($resource);

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $approveImageHideRequest = $this->app->make(ApproveImageHideRequestInterface::class);
        $output = new ApproveImageHideRequestOutput();
        $approveImageHideRequest->process($input, $output);

        $result = $output->toArray();
        $this->assertSame('approved', $result['status']);
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
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $input = new ApproveImageHideRequestInput($imageIdentifier, $principalIdentifier, 'comment');

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageRepository->shouldReceive('findById')
            ->once()
            ->with($imageIdentifier)
            ->andReturn(null);
        $imageRepository->shouldNotReceive('save');

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $this->expectException(ImageNotFoundException::class);
        $approveImageHideRequest = $this->app->make(ApproveImageHideRequestInterface::class);
        $output = new ApproveImageHideRequestOutput();
        $approveImageHideRequest->process($input, $output);
    }

    /**
     * 異常系：hideRequestがpendingでない場合、ImageHideRequestNotPendingExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessNotPending(): void
    {
        $image = $this->createTestImageWithoutHideRequest();
        $imageIdentifier = $image->imageIdentifier();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
        $resource = new Resource(type: ResourceType::IMAGE);
        $input = new ApproveImageHideRequestInput($imageIdentifier, $principalIdentifier, 'comment');

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageRepository->shouldReceive('findById')
            ->once()
            ->with($imageIdentifier)
            ->andReturn($image);
        $imageRepository->shouldNotReceive('save');

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($principalIdentifier)
            ->andReturn($principal);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(true);

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);
        $imageAuthorizationResourceBuilder->shouldReceive('buildFromImage')
            ->once()
            ->with($image)
            ->andReturn($resource);

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $this->expectException(ImageHideRequestNotPendingException::class);
        $approveImageHideRequest = $this->app->make(ApproveImageHideRequestInterface::class);
        $output = new ApproveImageHideRequestOutput();
        $approveImageHideRequest->process($input, $output);
    }

    /**
     * 異常系：Principalが見つからない場合、PrincipalNotFoundExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessPrincipalNotFound(): void
    {
        $image = $this->createTestImageWithPendingHideRequest();
        $imageIdentifier = $image->imageIdentifier();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $input = new ApproveImageHideRequestInput($imageIdentifier, $principalIdentifier, 'comment');

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageRepository->shouldReceive('findById')
            ->once()
            ->with($imageIdentifier)
            ->andReturn($image);
        $imageRepository->shouldNotReceive('save');

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($principalIdentifier)
            ->andReturn(null);

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $this->expectException(PrincipalNotFoundException::class);
        $approveImageHideRequest = $this->app->make(ApproveImageHideRequestInterface::class);
        $output = new ApproveImageHideRequestOutput();
        $approveImageHideRequest->process($input, $output);
    }

    /**
     * 異常系：権限がない場合、DisallowedExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessDisallowed(): void
    {
        $image = $this->createTestImageWithPendingHideRequest();
        $imageIdentifier = $image->imageIdentifier();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
        $resource = new Resource(type: ResourceType::IMAGE);

        $input = new ApproveImageHideRequestInput($imageIdentifier, $principalIdentifier, 'comment');

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageRepository->shouldReceive('findById')
            ->once()
            ->with($imageIdentifier)
            ->andReturn($image);
        $imageRepository->shouldNotReceive('save');

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')
            ->once()
            ->with($principalIdentifier)
            ->andReturn($principal);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturn(false);

        $imageAuthorizationResourceBuilder = Mockery::mock(ImageAuthorizationResourceBuilderInterface::class);
        $imageAuthorizationResourceBuilder->shouldReceive('buildFromImage')
            ->once()
            ->with($image)
            ->andReturn($resource);

        $this->app->instance(ImageRepositoryInterface::class, $imageRepository);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(ImageAuthorizationResourceBuilderInterface::class, $imageAuthorizationResourceBuilder);

        $this->expectException(DisallowedException::class);
        $approveImageHideRequest = $this->app->make(ApproveImageHideRequestInterface::class);
        $output = new ApproveImageHideRequestOutput();
        $approveImageHideRequest->process($input, $output);
    }

    private function createTestImageWithPendingHideRequest(): Image
    {
        $hideRequest = new HideRequest(
            'Test Requester',
            'requester@example.com',
            'Privacy concern',
            ImageHideRequestStatus::PENDING,
            new DateTimeImmutable(),
            null,
            null,
            null,
        );

        return new Image(
            new ImageIdentifier(StrTestHelper::generateUuid()),
            ResourceType::TALENT,
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new ImagePath('images/test.png'),
            ImageUsage::PROFILE,
            1,
            'https://example.com/source',
            'Example Source',
            'Profile image of talent',
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
    }

    private function createTestImageWithoutHideRequest(): Image
    {
        return new Image(
            new ImageIdentifier(StrTestHelper::generateUuid()),
            ResourceType::TALENT,
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new ImagePath('images/test.png'),
            ImageUsage::PROFILE,
            1,
            'https://example.com/source',
            'Example Source',
            'Profile image of talent',
            false,
            null,
            null,
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new DateTimeImmutable(),
            null,
            null,
            null,
            null,
        );
    }
}
