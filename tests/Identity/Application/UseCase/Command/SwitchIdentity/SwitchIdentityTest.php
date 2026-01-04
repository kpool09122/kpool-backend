<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\SwitchIdentity;

use DateTimeImmutable;
use DomainException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Identity\Application\Service\DelegationValidatorInterface;
use Source\Identity\Application\UseCase\Command\SwitchIdentity\SwitchIdentity;
use Source\Identity\Application\UseCase\Command\SwitchIdentity\SwitchIdentityInput;
use Source\Identity\Application\UseCase\Command\SwitchIdentity\SwitchIdentityInterface;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Identity\Domain\ValueObject\HashedPassword;
use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Identity\Domain\ValueObject\UserName;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SwitchIdentityTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $delegationValidator = Mockery::mock(DelegationValidatorInterface::class);
        $authService = Mockery::mock(AuthServiceInterface::class);

        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(DelegationValidatorInterface::class, $delegationValidator);
        $this->app->instance(AuthServiceInterface::class, $authService);

        $useCase = $this->app->make(SwitchIdentityInterface::class);

        $this->assertInstanceOf(SwitchIdentity::class, $useCase);
    }

    /**
     * 正常系: 委譲IDから元のIDに戻ることができること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws IdentityNotFoundException
     */
    public function testProcessWhenSwitchingBackToOriginal(): void
    {
        $originalIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegatedIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());

        $originalIdentity = $this->createIdentity($originalIdentityId);
        $delegatedIdentity = $this->createDelegatedIdentity(
            $delegatedIdentityId,
            $delegationId,
            $originalIdentityId
        );

        $input = new SwitchIdentityInput($delegatedIdentityId, null);

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')
            ->once()
            ->with($delegatedIdentityId)
            ->andReturn($delegatedIdentity);
        $identityRepository->shouldReceive('findById')
            ->once()
            ->with($originalIdentityId)
            ->andReturn($originalIdentity);

        $delegationValidator = Mockery::mock(DelegationValidatorInterface::class);

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('logout')
            ->once()
            ->andReturnNull();
        $authService->shouldReceive('login')
            ->once()
            ->with($originalIdentity)
            ->andReturn($originalIdentity);

        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(DelegationValidatorInterface::class, $delegationValidator);
        $this->app->instance(AuthServiceInterface::class, $authService);

        $useCase = $this->app->make(SwitchIdentityInterface::class);

        $result = $useCase->process($input);

        $this->assertSame($originalIdentity, $result);
    }

    /**
     * 正常系: 元のIDから委譲IDに切り替えることができること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws IdentityNotFoundException
     */
    public function testProcessWhenSwitchingToDelegatedIdentity(): void
    {
        $originalIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegatedIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());

        $originalIdentity = $this->createIdentity($originalIdentityId);
        $delegatedIdentity = $this->createDelegatedIdentity(
            $delegatedIdentityId,
            $delegationId,
            $originalIdentityId
        );

        $input = new SwitchIdentityInput($originalIdentityId, $delegationId);

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')
            ->once()
            ->with($originalIdentityId)
            ->andReturn($originalIdentity);
        $identityRepository->shouldReceive('findByDelegation')
            ->once()
            ->with($delegationId)
            ->andReturn($delegatedIdentity);

        $delegationValidator = Mockery::mock(DelegationValidatorInterface::class);
        $delegationValidator->shouldReceive('isValid')
            ->once()
            ->with($delegationId)
            ->andReturn(true);

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('logout')
            ->once()
            ->andReturnNull();
        $authService->shouldReceive('login')
            ->once()
            ->with($delegatedIdentity)
            ->andReturn($delegatedIdentity);

        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(DelegationValidatorInterface::class, $delegationValidator);
        $this->app->instance(AuthServiceInterface::class, $authService);

        $useCase = $this->app->make(SwitchIdentityInterface::class);

        $result = $useCase->process($input);

        $this->assertSame($delegatedIdentity, $result);
    }

    /**
     * 正常系: 委譲IDから別の委譲IDに切り替えることができること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws IdentityNotFoundException
     */
    public function testProcessWhenSwitchingFromDelegatedToAnotherDelegated(): void
    {
        $originalIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $currentDelegatedIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $currentDelegationId = new DelegationIdentifier(StrTestHelper::generateUuid());
        $targetDelegatedIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $targetDelegationId = new DelegationIdentifier(StrTestHelper::generateUuid());

        $originalIdentity = $this->createIdentity($originalIdentityId);
        $currentDelegatedIdentity = $this->createDelegatedIdentity(
            $currentDelegatedIdentityId,
            $currentDelegationId,
            $originalIdentityId
        );
        $targetDelegatedIdentity = $this->createDelegatedIdentity(
            $targetDelegatedIdentityId,
            $targetDelegationId,
            $originalIdentityId
        );

        $input = new SwitchIdentityInput($currentDelegatedIdentityId, $targetDelegationId);

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')
            ->once()
            ->with($currentDelegatedIdentityId)
            ->andReturn($currentDelegatedIdentity);
        $identityRepository->shouldReceive('findById')
            ->once()
            ->with($originalIdentityId)
            ->andReturn($originalIdentity);
        $identityRepository->shouldReceive('findByDelegation')
            ->once()
            ->with($targetDelegationId)
            ->andReturn($targetDelegatedIdentity);

        $delegationValidator = Mockery::mock(DelegationValidatorInterface::class);
        $delegationValidator->shouldReceive('isValid')
            ->once()
            ->with($targetDelegationId)
            ->andReturn(true);

        $authService = Mockery::mock(AuthServiceInterface::class);
        $authService->shouldReceive('logout')
            ->once()
            ->andReturnNull();
        $authService->shouldReceive('login')
            ->once()
            ->with($targetDelegatedIdentity)
            ->andReturn($targetDelegatedIdentity);

        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(DelegationValidatorInterface::class, $delegationValidator);
        $this->app->instance(AuthServiceInterface::class, $authService);

        $useCase = $this->app->make(SwitchIdentityInterface::class);

        $result = $useCase->process($input);

        $this->assertSame($targetDelegatedIdentity, $result);
    }

    /**
     * 異常系: 現在のIDが見つからない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsIdentityNotFoundExceptionWhenCurrentIdentityNotFound(): void
    {
        $currentIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());

        $input = new SwitchIdentityInput($currentIdentityId, $delegationId);

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')
            ->once()
            ->with($currentIdentityId)
            ->andReturnNull();

        $delegationValidator = Mockery::mock(DelegationValidatorInterface::class);
        $authService = Mockery::mock(AuthServiceInterface::class);

        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(DelegationValidatorInterface::class, $delegationValidator);
        $this->app->instance(AuthServiceInterface::class, $authService);

        $useCase = $this->app->make(SwitchIdentityInterface::class);

        $this->expectException(IdentityNotFoundException::class);
        $this->expectExceptionMessage('Current identity not found.');

        $useCase->process($input);
    }

    /**
     * 異常系: 元のIDが見つからない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsIdentityNotFoundExceptionWhenOriginalIdentityNotFound(): void
    {
        $originalIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegatedIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());

        $delegatedIdentity = $this->createDelegatedIdentity(
            $delegatedIdentityId,
            $delegationId,
            $originalIdentityId
        );

        $input = new SwitchIdentityInput($delegatedIdentityId, null);

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')
            ->once()
            ->with($delegatedIdentityId)
            ->andReturn($delegatedIdentity);
        $identityRepository->shouldReceive('findById')
            ->once()
            ->with($originalIdentityId)
            ->andReturnNull();

        $delegationValidator = Mockery::mock(DelegationValidatorInterface::class);
        $authService = Mockery::mock(AuthServiceInterface::class);

        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(DelegationValidatorInterface::class, $delegationValidator);
        $this->app->instance(AuthServiceInterface::class, $authService);

        $useCase = $this->app->make(SwitchIdentityInterface::class);

        $this->expectException(IdentityNotFoundException::class);
        $this->expectExceptionMessage('Original identity not found.');

        $useCase->process($input);
    }

    /**
     * 異常系: すでに元のIDを使用している場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsDomainExceptionWhenAlreadyUsingOriginal(): void
    {
        $originalIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());

        $originalIdentity = $this->createIdentity($originalIdentityId);

        $input = new SwitchIdentityInput($originalIdentityId, null);

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')
            ->once()
            ->with($originalIdentityId)
            ->andReturn($originalIdentity);

        $delegationValidator = Mockery::mock(DelegationValidatorInterface::class);
        $authService = Mockery::mock(AuthServiceInterface::class);

        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(DelegationValidatorInterface::class, $delegationValidator);
        $this->app->instance(AuthServiceInterface::class, $authService);

        $useCase = $this->app->make(SwitchIdentityInterface::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Already using original identity.');

        $useCase->process($input);
    }

    /**
     * 異常系: 委譲が無効な場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsDomainExceptionWhenDelegationIsNotValid(): void
    {
        $originalIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());

        $originalIdentity = $this->createIdentity($originalIdentityId);

        $input = new SwitchIdentityInput($originalIdentityId, $delegationId);

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')
            ->once()
            ->with($originalIdentityId)
            ->andReturn($originalIdentity);

        $delegationValidator = Mockery::mock(DelegationValidatorInterface::class);
        $delegationValidator->shouldReceive('isValid')
            ->once()
            ->with($delegationId)
            ->andReturn(false);

        $authService = Mockery::mock(AuthServiceInterface::class);

        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(DelegationValidatorInterface::class, $delegationValidator);
        $this->app->instance(AuthServiceInterface::class, $authService);

        $useCase = $this->app->make(SwitchIdentityInterface::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Delegation is not valid.');

        $useCase->process($input);
    }

    /**
     * 異常系: 委譲IDが見つからない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsIdentityNotFoundExceptionWhenDelegatedIdentityNotFound(): void
    {
        $originalIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());

        $originalIdentity = $this->createIdentity($originalIdentityId);

        $input = new SwitchIdentityInput($originalIdentityId, $delegationId);

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')
            ->once()
            ->with($originalIdentityId)
            ->andReturn($originalIdentity);
        $identityRepository->shouldReceive('findByDelegation')
            ->once()
            ->with($delegationId)
            ->andReturnNull();

        $delegationValidator = Mockery::mock(DelegationValidatorInterface::class);
        $delegationValidator->shouldReceive('isValid')
            ->once()
            ->with($delegationId)
            ->andReturn(true);

        $authService = Mockery::mock(AuthServiceInterface::class);

        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(DelegationValidatorInterface::class, $delegationValidator);
        $this->app->instance(AuthServiceInterface::class, $authService);

        $useCase = $this->app->make(SwitchIdentityInterface::class);

        $this->expectException(IdentityNotFoundException::class);
        $this->expectExceptionMessage('Delegated identity not found.');

        $useCase->process($input);
    }

    /**
     * 異常系: 委譲が現在のIDに属していない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsDomainExceptionWhenDelegationDoesNotBelongToCurrentIdentity(): void
    {
        $originalIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $otherOriginalIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegatedIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());

        $originalIdentity = $this->createIdentity($originalIdentityId);
        $delegatedIdentity = $this->createDelegatedIdentity(
            $delegatedIdentityId,
            $delegationId,
            $otherOriginalIdentityId
        );

        $input = new SwitchIdentityInput($originalIdentityId, $delegationId);

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')
            ->once()
            ->with($originalIdentityId)
            ->andReturn($originalIdentity);
        $identityRepository->shouldReceive('findByDelegation')
            ->once()
            ->with($delegationId)
            ->andReturn($delegatedIdentity);

        $delegationValidator = Mockery::mock(DelegationValidatorInterface::class);
        $delegationValidator->shouldReceive('isValid')
            ->once()
            ->with($delegationId)
            ->andReturn(true);

        $authService = Mockery::mock(AuthServiceInterface::class);

        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(DelegationValidatorInterface::class, $delegationValidator);
        $this->app->instance(AuthServiceInterface::class, $authService);

        $useCase = $this->app->make(SwitchIdentityInterface::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Delegation does not belong to the current identity.');

        $useCase->process($input);
    }

    private function createIdentity(IdentityIdentifier $identityIdentifier): Identity
    {
        return new Identity(
            $identityIdentifier,
            new UserName('test-user'),
            new Email('user@example.com'),
            Language::KOREAN,
            new ImagePath('/resources/path/test.png'),
            HashedPassword::fromPlain(new PlainPassword('PlainPass1!')),
            new DateTimeImmutable(),
        );
    }

    private function createDelegatedIdentity(
        IdentityIdentifier $identityIdentifier,
        DelegationIdentifier $delegationIdentifier,
        IdentityIdentifier $originalIdentityIdentifier
    ): Identity {
        return new Identity(
            $identityIdentifier,
            new UserName('delegated-user'),
            new Email('delegated@example.com'),
            Language::KOREAN,
            new ImagePath('/resources/path/test.png'),
            HashedPassword::fromPlain(new PlainPassword('PlainPass1!')),
            new DateTimeImmutable(),
            [],
            $delegationIdentifier,
            $originalIdentityIdentifier,
        );
    }
}
