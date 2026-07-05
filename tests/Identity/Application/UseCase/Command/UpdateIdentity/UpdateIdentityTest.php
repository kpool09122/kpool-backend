<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\UpdateIdentity;

use DateTimeImmutable;
use Mockery;
use Source\Identity\Application\UseCase\Command\UpdateIdentity\UpdateIdentity;
use Source\Identity\Application\UseCase\Command\UpdateIdentity\UpdateIdentityInput;
use Source\Identity\Application\UseCase\Command\UpdateIdentity\UpdateIdentityOutput;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Exception\InvalidDelegationException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Identity\Domain\ValueObject\HashedPassword;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Shared\Application\DTO\ImageUploadResult;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class UpdateIdentityTest extends TestCase
{
    public function testProcessUpdatesIdentityProfileAndRefreshesAuthenticatedIdentity(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $identity = $this->createIdentity($identityIdentifier);
        $updatedName = new IdentityName('updated-identity');
        $base64Image = base64_encode('dummy-image');
        $resizedPath = new ImagePath('images/profile_resized.webp');

        /** @var IdentityRepositoryInterface&\Mockery\MockInterface $repository */
        $repository = Mockery::mock(IdentityRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with($identityIdentifier)->andReturn($identity);
        $repository->shouldReceive('save')->once()->with(Mockery::on(
            static fn (Identity $saved): bool => (string) $saved->identityName() === 'updated-identity'
                && $saved->language() === Language::KOREAN
                && (string) $saved->profileImage() === 'images/profile_resized.webp'
        ))->andReturnNull();

        /** @var ImageServiceInterface&\Mockery\MockInterface $imageService */
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('upload')
            ->once()
            ->with($base64Image)
            ->andReturn(new ImageUploadResult(new ImagePath('images/profile_original.webp'), $resizedPath));

        /** @var AuthServiceInterface&\Mockery\MockInterface $authService */
        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('refreshAuthenticatedIdentity')->once()->with($identity)->andReturnNull();

        $input = new UpdateIdentityInput(
            identityIdentifier: $identityIdentifier,
            delegationIdentifier: null,
            originalIdentityIdentifier: null,
            identityName: $updatedName,
            language: Language::KOREAN,
            base64EncodedImage: $base64Image,
        );
        $output = new UpdateIdentityOutput();

        $useCase = new UpdateIdentity($repository, $imageService, $authService);
        $useCase->process($input, $output);

        $this->assertSame('updated-identity', $output->toArray()['identityName']);
        $this->assertSame('ko', $output->toArray()['language']);
        $this->assertSame('images/profile_resized.webp', $output->toArray()['profileImage']);
    }

    public function testProcessRejectsDelegatedSession(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        /** @var IdentityRepositoryInterface&\Mockery\MockInterface $repository */
        $repository = Mockery::mock(IdentityRepositoryInterface::class);
        $repository->shouldNotReceive('findById');
        /** @var ImageServiceInterface&\Mockery\MockInterface $imageService */
        $imageService = Mockery::mock(ImageServiceInterface::class);
        /** @var AuthServiceInterface&\Mockery\MockInterface $authService */
        $authService = Mockery::mock(AuthServiceInterface::class);

        $this->expectException(InvalidDelegationException::class);

        (new UpdateIdentity($repository, $imageService, $authService))->process(
            new UpdateIdentityInput(
                identityIdentifier: $identityIdentifier,
                delegationIdentifier: new DelegationIdentifier(StrTestHelper::generateUuid()),
                originalIdentityIdentifier: null,
                identityName: null,
                language: null,
                base64EncodedImage: null,
            ),
            new UpdateIdentityOutput(),
        );
    }

    public function testProcessThrowsWhenTargetIdentityNotFound(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        /** @var IdentityRepositoryInterface&\Mockery\MockInterface $repository */
        $repository = Mockery::mock(IdentityRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with($identityIdentifier)->andReturnNull();
        /** @var ImageServiceInterface&\Mockery\MockInterface $imageService */
        $imageService = Mockery::mock(ImageServiceInterface::class);
        /** @var AuthServiceInterface&\Mockery\MockInterface $authService */
        $authService = Mockery::mock(AuthServiceInterface::class);

        $this->expectException(IdentityNotFoundException::class);

        (new UpdateIdentity($repository, $imageService, $authService))->process(
            new UpdateIdentityInput(
                identityIdentifier: $identityIdentifier,
                delegationIdentifier: null,
                originalIdentityIdentifier: null,
                identityName: null,
                language: null,
                base64EncodedImage: null,
            ),
            new UpdateIdentityOutput(),
        );
    }

    private function createIdentity(IdentityIdentifier $identityIdentifier): Identity
    {
        return new Identity(
            $identityIdentifier,
            new IdentityName('identity-name'),
            new Email('identity@example.com'),
            Language::ENGLISH,
            null,
            HashedPassword::fromPlain(new PlainPassword('Password123!')),
            new DateTimeImmutable(),
        );
    }
}
