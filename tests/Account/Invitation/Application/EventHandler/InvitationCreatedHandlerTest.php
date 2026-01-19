<?php

declare(strict_types=1);

namespace Tests\Account\Invitation\Application\EventHandler;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Invitation\Application\EventHandler\InvitationCreatedHandler;
use Source\Account\Invitation\Domain\Entity\Invitation;
use Source\Account\Invitation\Domain\Event\InvitationCreated;
use Source\Account\Invitation\Domain\Repository\InvitationRepositoryInterface;
use Source\Account\Invitation\Domain\Service\InvitationMailServiceInterface;
use Source\Account\Invitation\Domain\ValueObject\InvitationIdentifier;
use Source\Account\Invitation\Domain\ValueObject\InvitationStatus;
use Source\Account\Invitation\Domain\ValueObject\InvitationToken;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class InvitationCreatedHandlerTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作していること
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $invitationRepository = Mockery::mock(InvitationRepositoryInterface::class);
        $invitationMailService = Mockery::mock(InvitationMailServiceInterface::class);

        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(InvitationMailServiceInterface::class, $invitationMailService);

        $handler = $this->app->make(InvitationCreatedHandler::class);

        $this->assertInstanceOf(InvitationCreatedHandler::class, $handler);
    }

    /**
     * 正常系: 招待メールが送信されること
     *
     * @throws BindingResolutionException
     */
    public function testHandleSendsInvitationEmail(): void
    {
        $data = $this->createTestData();

        $invitationRepository = Mockery::mock(InvitationRepositoryInterface::class);
        $invitationRepository->shouldReceive('findByToken')
            ->once()
            ->with($data->token)
            ->andReturn($data->invitation);

        $invitationMailService = Mockery::mock(InvitationMailServiceInterface::class);
        $invitationMailService->shouldReceive('sendInvitationEmail')
            ->once()
            ->with($data->invitation);

        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(InvitationMailServiceInterface::class, $invitationMailService);

        $handler = $this->app->make(InvitationCreatedHandler::class);
        $handler->handle($data->event);
    }

    /**
     * 正常系: 招待が見つからない場合、メールを送信しないこと
     *
     * @throws BindingResolutionException
     */
    public function testHandleDoesNothingWhenInvitationNotFound(): void
    {
        $data = $this->createTestData();

        $invitationRepository = Mockery::mock(InvitationRepositoryInterface::class);
        $invitationRepository->shouldReceive('findByToken')
            ->once()
            ->with($data->token)
            ->andReturnNull();

        $invitationMailService = Mockery::mock(InvitationMailServiceInterface::class);
        $invitationMailService->shouldNotReceive('sendInvitationEmail');

        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(InvitationMailServiceInterface::class, $invitationMailService);

        $handler = $this->app->make(InvitationCreatedHandler::class);
        $handler->handle($data->event);
    }

    private function createTestData(): InvitationCreatedHandlerTestData
    {
        $invitationIdentifier = new InvitationIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $inviterIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $email = new Email('invitee@example.com');
        $token = new InvitationToken(bin2hex(random_bytes(32)));

        $event = new InvitationCreated(
            invitationIdentifier: $invitationIdentifier,
            accountIdentifier: $accountIdentifier,
            invitedByIdentityIdentifier: $inviterIdentityIdentifier,
            email: $email,
            token: $token,
        );

        $invitation = new Invitation(
            $invitationIdentifier,
            $accountIdentifier,
            $inviterIdentityIdentifier,
            $email,
            $token,
            InvitationStatus::PENDING,
            new DateTimeImmutable('+7 days'),
            null,
            null,
            new DateTimeImmutable(),
        );

        return new InvitationCreatedHandlerTestData(
            $token,
            $event,
            $invitation,
        );
    }
}

readonly class InvitationCreatedHandlerTestData
{
    public function __construct(
        public InvitationToken $token,
        public InvitationCreated $event,
        public Invitation $invitation,
    ) {
    }
}
