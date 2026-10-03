<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\WithdrawIdentity;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\IdentityWithdrawalServiceInterface;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Application\UseCase\Command\WithdrawIdentity\WithdrawIdentity;
use Source\Identity\Domain\Event\IdentityWithdrawing;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Identity\Domain\ValueObject\StepUpAuthentication;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationMethod;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class WithdrawIdentityTest extends TestCase
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
        /** @var IdentityWithdrawalServiceInterface&MockInterface $withdrawal */
        $withdrawal = Mockery::mock(IdentityWithdrawalServiceInterface::class);
        $withdrawal->shouldNotReceive('archive');
        $withdrawal->shouldNotReceive('delete');
        /** @var EventDispatcherInterface&MockInterface $events */
        $events = Mockery::mock(EventDispatcherInterface::class);
        $events->shouldNotReceive('dispatch');

        $this->expectException(StepUpAuthenticationRequiredException::class);
        (new WithdrawIdentity($authentication, $withdrawal, $events))->process($identityIdentifier);
    }

    public function testArchivesBeforeDispatchAndDeletesAfterServiceCleanup(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        /** @var StepUpAuthenticationStorageServiceInterface&MockInterface $authentication */
        $authentication = Mockery::mock(StepUpAuthenticationStorageServiceInterface::class);
        $authentication->shouldReceive('requireValid')->once()->with($identityIdentifier, StepUpAuthenticationScope::RECENT_AUTHENTICATION)
            ->andReturn(new StepUpAuthentication($identityIdentifier, StepUpAuthenticationMethod::PASSKEY, new DateTimeImmutable(), StepUpAuthenticationScope::RECENT_AUTHENTICATION, new DateTimeImmutable('+10 minutes')));
        /** @var IdentityWithdrawalServiceInterface&MockInterface $withdrawal */
        $withdrawal = Mockery::mock(IdentityWithdrawalServiceInterface::class);
        $withdrawal->shouldReceive('archive')->once()->with($identityIdentifier, Mockery::type(DateTimeImmutable::class))->globally()->ordered();
        /** @var EventDispatcherInterface&MockInterface $events */
        $events = Mockery::mock(EventDispatcherInterface::class);
        $events->shouldReceive('dispatch')->once()->with(Mockery::on(static fn (object $event): bool => $event instanceof IdentityWithdrawing && $event->identityIdentifier === $identityIdentifier))->globally()->ordered();
        $withdrawal->shouldReceive('delete')->once()->with($identityIdentifier)->globally()->ordered();

        (new WithdrawIdentity($authentication, $withdrawal, $events))->process($identityIdentifier);
        $this->addToAssertionCount(1);
    }
}
