<?php

declare(strict_types=1);

namespace Tests\Account\Invitation\Infrastructure\Service;

use Mockery;
use Mockery\MockInterface;
use Source\Account\Invitation\Domain\Entity\Invitation;
use Source\Account\Invitation\Domain\Exception\InvitationAlreadyUsedOrRevokedException;
use Source\Account\Invitation\Domain\Exception\InvitationExpiredException;
use Source\Account\Invitation\Domain\Repository\InvitationRepositoryInterface;
use Source\Account\Invitation\Infrastructure\Service\SignupInvitationValidator;
use Source\Identity\Application\Service\SignupInvitationValidatorInterface;
use Source\Identity\Domain\Exception\InvalidSignupInvitationException;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\OneTimeToken;
use Tests\TestCase;

class SignupInvitationValidatorTest extends TestCase
{
    public function testItIsBound(): void
    {
        $this->assertInstanceOf(
            SignupInvitationValidator::class,
            $this->app->make(SignupInvitationValidatorInterface::class),
        );
    }

    public function testItAcceptsAPendingInvitationForTheSameEmail(): void
    {
        $token = new OneTimeToken(str_repeat('a', OneTimeToken::TOKEN_LENGTH));
        $email = new Email('invited@example.com');
        $invitation = Mockery::mock(Invitation::class);
        $invitation->shouldReceive('email')->once()->andReturn($email);
        $invitation->shouldReceive('assertAcceptable')->once();
        /** @var MockInterface&InvitationRepositoryInterface $repository */
        $repository = Mockery::mock(InvitationRepositoryInterface::class);
        $repository->shouldReceive('findByToken')->once()->with($token)->andReturn($invitation);

        (new SignupInvitationValidator($repository))->validate($token, $email);
        $this->addToAssertionCount(1);
    }

    public function testItRejectsAnUnknownInvitation(): void
    {
        $token = new OneTimeToken(str_repeat('a', OneTimeToken::TOKEN_LENGTH));
        /** @var MockInterface&InvitationRepositoryInterface $repository */
        $repository = Mockery::mock(InvitationRepositoryInterface::class);
        $repository->shouldReceive('findByToken')->once()->with($token)->andReturnNull();

        $this->expectException(InvalidSignupInvitationException::class);
        (new SignupInvitationValidator($repository))->validate($token, new Email('invited@example.com'));
    }

    public function testItRejectsAnEmailMismatchedInvitation(): void
    {
        $token = new OneTimeToken(str_repeat('a', OneTimeToken::TOKEN_LENGTH));
        $invitation = Mockery::mock(Invitation::class);
        $invitation->shouldReceive('email')->once()->andReturn(new Email('other@example.com'));
        $invitation->shouldNotReceive('assertAcceptable');
        /** @var MockInterface&InvitationRepositoryInterface $repository */
        $repository = Mockery::mock(InvitationRepositoryInterface::class);
        $repository->shouldReceive('findByToken')->once()->andReturn($invitation);

        $this->expectException(InvalidSignupInvitationException::class);
        (new SignupInvitationValidator($repository))->validate($token, new Email('invited@example.com'));
    }

    public function testItRejectsAnExpiredInvitationWithoutLeakingAccountExceptions(): void
    {
        $token = new OneTimeToken(str_repeat('a', OneTimeToken::TOKEN_LENGTH));
        $email = new Email('invited@example.com');
        $invitation = Mockery::mock(Invitation::class);
        $invitation->shouldReceive('email')->once()->andReturn($email);
        $invitation->shouldReceive('assertAcceptable')->once()->andThrow(new InvitationExpiredException());
        /** @var MockInterface&InvitationRepositoryInterface $repository */
        $repository = Mockery::mock(InvitationRepositoryInterface::class);
        $repository->shouldReceive('findByToken')->once()->andReturn($invitation);

        $this->expectException(InvalidSignupInvitationException::class);
        (new SignupInvitationValidator($repository))->validate($token, $email);
    }

    public function testItRejectsAUsedOrRevokedInvitationWithoutLeakingAccountExceptions(): void
    {
        $token = new OneTimeToken(str_repeat('a', OneTimeToken::TOKEN_LENGTH));
        $email = new Email('invited@example.com');
        $invitation = Mockery::mock(Invitation::class);
        $invitation->shouldReceive('email')->once()->andReturn($email);
        $invitation->shouldReceive('assertAcceptable')
            ->once()
            ->andThrow(new InvitationAlreadyUsedOrRevokedException());
        /** @var MockInterface&InvitationRepositoryInterface $repository */
        $repository = Mockery::mock(InvitationRepositoryInterface::class);
        $repository->shouldReceive('findByToken')->once()->andReturn($invitation);

        $this->expectException(InvalidSignupInvitationException::class);
        (new SignupInvitationValidator($repository))->validate($token, $email);
    }
}
