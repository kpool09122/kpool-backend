<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Application\UseCase\Command\ApproveImageDeletion;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Application\UseCase\Command\ApproveImageDeletion\ApproveImageDeletion;
use Source\Wiki\Image\Application\UseCase\Command\ApproveImageDeletion\ApproveImageDeletionInput;
use Source\Wiki\Image\Application\UseCase\Command\ApproveImageDeletion\ApproveImageDeletionInterface;
use Source\Wiki\Image\Application\UseCase\Command\ApproveImageDeletion\ApproveImageDeletionOutput;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Exception\ImageDeletionRequestNotPendingException;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Image\Domain\Service\ImageAuthorizationResourceBuilderInterface;
use Source\Wiki\Image\Domain\ValueObject\DeletionRequest;
use Source\Wiki\Image\Domain\ValueObject\RightsConfirmationAgreed;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Entity\WikiSnapshot;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiSnapshotRepositoryInterface;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ApproveImageDeletionTest extends TestCase
{
    private function bindReferenceRepositories(?ImageIdentifier $expectedImageIdentifier = null): void
    {
        foreach ([
            WikiRepositoryInterface::class => Wiki::class,
            DraftWikiRepositoryInterface::class => DraftWiki::class,
            WikiSnapshotRepositoryInterface::class => WikiSnapshot::class,
        ] as $interface => $entityClass) {
            $repository = Mockery::mock($interface);
            if ($expectedImageIdentifier !== null) {
                $entity = Mockery::mock($entityClass);
                $entity->shouldReceive('setImageIdentifier')
                    ->once()
                    ->with(null)
                    ->andReturn(null);
                $repository->shouldReceive('findByImageIdentifier')
                    ->once()
                    ->with($expectedImageIdentifier)
                    ->andReturn([$entity]);
                $repository->shouldReceive('save')
                    ->once()
                    ->with($entity)
                    ->andReturn(null);
            } else {
                $repository->shouldReceive('findByImageIdentifier')->byDefault()->andReturn([]);
            }
            $this->app->instance($interface, $repository);
        }
    }

    private function bindImageService(?ImagePath $expectedImagePath = null): void
    {
        $imageService = Mockery::mock(ImageServiceInterface::class);
        if ($expectedImagePath !== null) {
            $imageService->shouldReceive('delete')
                ->once()
                ->with($expectedImagePath)
                ->andReturn(true);
        } else {
            $imageService->shouldNotReceive('delete');
        }

        $this->app->instance(ImageServiceInterface::class, $imageService);
    }

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
        $this->bindReferenceRepositories();
        $this->bindImageService();

        $approveImageDeletionRequest = $this->app->make(ApproveImageDeletionInterface::class);
        $this->assertInstanceOf(ApproveImageDeletion::class, $approveImageDeletionRequest);
    }

    /**
     * 正常系：正しくdeletionRequestが承認されること.
     *
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $image = $this->createTestImageWithPendingDeletionRequest();
        $imageIdentifier = $image->imageIdentifier();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
        $resource = new Resource(type: ResourceType::IMAGE);

        $input = new ApproveImageDeletionInput($imageIdentifier, $principalIdentifier);

        $imageRepository = Mockery::mock(ImageRepositoryInterface::class);
        $imageRepository->shouldReceive('findById')
            ->once()
            ->with($imageIdentifier)
            ->andReturn($image);
        $imageRepository->shouldReceive('save')
            ->once()
            ->andReturn(null);
        $imageRepository->shouldReceive('delete')
            ->once()
            ->with($imageIdentifier)
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
        $this->bindReferenceRepositories($imageIdentifier);
        $this->bindImageService($image->imagePath());

        $approveImageDeletionRequest = $this->app->make(ApproveImageDeletionInterface::class);
        $output = new ApproveImageDeletionOutput();
        $approveImageDeletionRequest->process($input, $output);

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
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $input = new ApproveImageDeletionInput($imageIdentifier, $principalIdentifier);

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

        $this->bindReferenceRepositories();
        $this->bindImageService();

        $this->expectException(ImageNotFoundException::class);
        $approveImageDeletionRequest = $this->app->make(ApproveImageDeletionInterface::class);
        $output = new ApproveImageDeletionOutput();
        $approveImageDeletionRequest->process($input, $output);
    }

    /**
     * 異常系：deletionRequestがpendingでない場合、ImageDeletionRequestNotPendingExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessNotPending(): void
    {
        $image = $this->createTestImageWithoutDeletionRequest();
        $imageIdentifier = $image->imageIdentifier();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
        $resource = new Resource(type: ResourceType::IMAGE);
        $input = new ApproveImageDeletionInput($imageIdentifier, $principalIdentifier);

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

        $this->bindReferenceRepositories();
        $this->bindImageService();

        $this->expectException(ImageDeletionRequestNotPendingException::class);
        $approveImageDeletionRequest = $this->app->make(ApproveImageDeletionInterface::class);
        $output = new ApproveImageDeletionOutput();
        $approveImageDeletionRequest->process($input, $output);
    }

    /**
     * 異常系：Principalが見つからない場合、PrincipalNotFoundExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessPrincipalNotFound(): void
    {
        $image = $this->createTestImageWithPendingDeletionRequest();
        $imageIdentifier = $image->imageIdentifier();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $input = new ApproveImageDeletionInput($imageIdentifier, $principalIdentifier);

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

        $this->bindReferenceRepositories();
        $this->bindImageService();

        $this->expectException(PrincipalNotFoundException::class);
        $approveImageDeletionRequest = $this->app->make(ApproveImageDeletionInterface::class);
        $output = new ApproveImageDeletionOutput();
        $approveImageDeletionRequest->process($input, $output);
    }

    /**
     * 異常系：権限がない場合、DisallowedExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessDisallowed(): void
    {
        $image = $this->createTestImageWithPendingDeletionRequest();
        $imageIdentifier = $image->imageIdentifier();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, new IdentityIdentifier(StrTestHelper::generateUuid()), null, [], []);
        $resource = new Resource(type: ResourceType::IMAGE);

        $input = new ApproveImageDeletionInput($imageIdentifier, $principalIdentifier);

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

        $this->bindReferenceRepositories();
        $this->bindImageService();

        $this->expectException(DisallowedException::class);
        $approveImageDeletionRequest = $this->app->make(ApproveImageDeletionInterface::class);
        $output = new ApproveImageDeletionOutput();
        $approveImageDeletionRequest->process($input, $output);
    }

    private function createTestImageWithPendingDeletionRequest(): Image
    {
        $deletionRequest = new DeletionRequest(
            'Test Requester',
            'requester@example.com',
            'Privacy concern',
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

    private function createTestImageWithoutDeletionRequest(): Image
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
}
