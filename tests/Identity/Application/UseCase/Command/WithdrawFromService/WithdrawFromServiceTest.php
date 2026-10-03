<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\WithdrawFromService;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\ActorContextServiceInterface;
use Source\Identity\Application\Service\IdentitySessionServiceInterface;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Application\UseCase\Command\WithdrawFromService\WithdrawFromService;
use Source\Identity\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInput;
use Source\Identity\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceOutput;
use Source\Identity\Domain\Entity\ArchivedIdentity;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Event\IdentityWithdrawing;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Identity\Domain\Factory\ArchivedIdentityFactoryInterface;
use Source\Identity\Domain\Repository\ArchivedIdentityRepositoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\ValueObject\ArchivedIdentityIdentifier;
use Source\Identity\Domain\ValueObject\StepUpAuthentication;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationMethod;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;

class WithdrawFromServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testMissingRecentAuthenticationDoesNotArchiveOrDelete(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        /** @var StepUpAuthenticationStorageServiceInterface&MockInterface $authentication */
        $authentication = Mockery::mock(StepUpAuthenticationStorageServiceInterface::class);
        $authentication->shouldReceive('requireValid')->once()
            ->with($identityIdentifier, StepUpAuthenticationScope::RECENT_AUTHENTICATION)
            ->andThrow(new StepUpAuthenticationRequiredException());
        /** @var ActorContextServiceInterface&MockInterface $actorContextService */
        $actorContextService = Mockery::mock(ActorContextServiceInterface::class);
        /** @var IdentityRepositoryInterface&MockInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldNotReceive('findById');
        /** @var ArchivedIdentityFactoryInterface&MockInterface $archivedIdentityFactory */
        $archivedIdentityFactory = Mockery::mock(ArchivedIdentityFactoryInterface::class);
        $archivedIdentityFactory->shouldNotReceive('create');
        /** @var ArchivedIdentityRepositoryInterface&MockInterface $archivedIdentityRepository */
        $archivedIdentityRepository = Mockery::mock(ArchivedIdentityRepositoryInterface::class);
        $archivedIdentityRepository->shouldNotReceive('save');
        $actorContextService->shouldNotReceive('forget');
        $identityRepository->shouldNotReceive('delete');
        /** @var EventDispatcherInterface&MockInterface $events */
        $events = Mockery::mock(EventDispatcherInterface::class);
        $events->shouldNotReceive('dispatch');
        /** @var IdentitySessionServiceInterface&MockInterface $identitySessionService */
        $identitySessionService = Mockery::mock(IdentitySessionServiceInterface::class);
        $identitySessionService->shouldNotReceive('terminate');

        /** @var ImageServiceInterface&MockInterface $imageService */
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldNotReceive('delete');

        $this->expectException(StepUpAuthenticationRequiredException::class);
        (new WithdrawFromService($authentication, $actorContextService, $events, $identitySessionService, $identityRepository, $archivedIdentityFactory, $archivedIdentityRepository, $imageService))->process(new WithdrawFromServiceInput($identityIdentifier), new WithdrawFromServiceOutput());
    }

    public function testArchivesBeforeDispatchAndDeletesAfterServiceCleanup(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        /** @var StepUpAuthenticationStorageServiceInterface&MockInterface $authentication */
        $authentication = Mockery::mock(StepUpAuthenticationStorageServiceInterface::class);
        $authentication->shouldReceive('requireValid')->once()->with($identityIdentifier, StepUpAuthenticationScope::RECENT_AUTHENTICATION)
            ->andReturn(new StepUpAuthentication($identityIdentifier, StepUpAuthenticationMethod::PASSKEY, new DateTimeImmutable(), StepUpAuthenticationScope::RECENT_AUTHENTICATION, new DateTimeImmutable('+10 minutes')));
        /** @var ActorContextServiceInterface&MockInterface $actorContextService */
        $actorContextService = Mockery::mock(ActorContextServiceInterface::class);
        $createdAt = new DateTimeImmutable('2020-01-01');
        $identity = Mockery::mock(Identity::class);
        $identity->shouldReceive('language')->once()->andReturn(Language::JAPANESE);
        $identity->shouldReceive('createdAt')->once()->andReturn($createdAt);
        $profileImage = new ImagePath('images/withdrawal.webp');
        $identity->shouldReceive('profileImage')->once()->andReturn($profileImage);
        /** @var IdentityRepositoryInterface&MockInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')->once()->with($identityIdentifier)->andReturn($identity);
        $archivedIdentity = new ArchivedIdentity(new ArchivedIdentityIdentifier(StrTestHelper::generateUuid()), $identityIdentifier, Language::JAPANESE, $createdAt, new DateTimeImmutable());
        /** @var ArchivedIdentityFactoryInterface&MockInterface $archivedIdentityFactory */
        $archivedIdentityFactory = Mockery::mock(ArchivedIdentityFactoryInterface::class);
        $archivedIdentityFactory->shouldReceive('create')->once()
            ->with($identityIdentifier, Language::JAPANESE, $createdAt)
            ->andReturn($archivedIdentity);
        /** @var ArchivedIdentityRepositoryInterface&MockInterface $archivedIdentityRepository */
        $archivedIdentityRepository = Mockery::mock(ArchivedIdentityRepositoryInterface::class);
        $archivedIdentityRepository->shouldReceive('save')->once()->with($archivedIdentity)->globally()->ordered();
        /** @var EventDispatcherInterface&MockInterface $events */
        $events = Mockery::mock(EventDispatcherInterface::class);
        $events->shouldReceive('dispatch')->once()->with(Mockery::on(
            static fn (object $event): bool => $event instanceof IdentityWithdrawing && $event->identityIdentifier === $identityIdentifier,
        ))->globally()->ordered();
        $identityRepository->shouldReceive('delete')->once()->with($identityIdentifier)->globally()->ordered();
        $actorContextService->shouldReceive('forget')->once()->with($identityIdentifier)->globally()->ordered();
        /** @var IdentitySessionServiceInterface&MockInterface $identitySessionService */
        $identitySessionService = Mockery::mock(IdentitySessionServiceInterface::class);
        $identitySessionService->shouldReceive('terminate')->once()->with($identityIdentifier)->globally()->ordered();

        /** @var ImageServiceInterface&MockInterface $imageService */
        $imageService = Mockery::mock(ImageServiceInterface::class);
        $imageService->shouldReceive('delete')->once()->with($profileImage)->andReturn(true)->globally()->ordered();

        (new WithdrawFromService($authentication, $actorContextService, $events, $identitySessionService, $identityRepository, $archivedIdentityFactory, $archivedIdentityRepository, $imageService))->process(new WithdrawFromServiceInput($identityIdentifier), new WithdrawFromServiceOutput());

        $this->addToAssertionCount(1);
    }
}
