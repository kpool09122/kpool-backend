<?php

declare(strict_types=1);

namespace Tests\Account\Invitation\Application\UseCase\Command\CreateInvitation;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Invitation\Application\Exception\DisallowedInvitationException;
use Source\Account\Invitation\Application\UseCase\Command\CreateInvitation\CreateInvitation;
use Source\Account\Invitation\Application\UseCase\Command\CreateInvitation\CreateInvitationInput;
use Source\Account\Invitation\Application\UseCase\Command\CreateInvitation\CreateInvitationInterface;
use Source\Account\Invitation\Application\UseCase\Command\CreateInvitation\CreateInvitationOutput;
use Source\Account\Invitation\Domain\Entity\Invitation;
use Source\Account\Invitation\Domain\Event\InvitationCreated;
use Source\Account\Invitation\Domain\Factory\InvitationFactoryInterface;
use Source\Account\Invitation\Domain\Repository\InvitationRepositoryInterface;
use Source\Account\Invitation\Domain\ValueObject\InvitationIdentifier;
use Source\Account\Invitation\Domain\ValueObject\InvitationStatus;
use Source\Shared\Domain\ValueObject\OneTimeToken;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreateInvitationTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作していること
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $this->app->instance(InvitationRepositoryInterface::class, Mockery::mock(InvitationRepositoryInterface::class));
        $this->app->instance(InvitationFactoryInterface::class, Mockery::mock(InvitationFactoryInterface::class));
        $this->app->instance(PolicyEvaluatorInterface::class, Mockery::mock(PolicyEvaluatorInterface::class));
        $this->app->instance(PrincipalRepositoryInterface::class, Mockery::mock(PrincipalRepositoryInterface::class));
        $this->app->instance(EventDispatcherInterface::class, Mockery::mock(EventDispatcherInterface::class));

        $useCase = $this->app->make(CreateInvitationInterface::class);

        $this->assertInstanceOf(CreateInvitation::class, $useCase);
    }

    /**
     * 正常系: PolicyEvaluatorが許可したユーザーは招待を作成できること
     *
     * @throws BindingResolutionException
     */
    public function testProcessWhenPolicyAllowsInvitationCreate(): void
    {
        $data = $this->createTestData();

        $this->bindPolicyEvaluator($data, true);

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(
                static fn ($event) => $event instanceof InvitationCreated
                    && (string) $event->invitationIdentifier === (string) $data->invitation->invitationIdentifier()
                    && (string) $event->accountIdentifier === (string) $data->accountIdentifier
                    && (string) $event->email === (string) $data->email
            ));

        $invitationRepository = Mockery::mock(InvitationRepositoryInterface::class);
        $invitationRepository->shouldReceive('findPendingByAccountAndEmail')
            ->once()
            ->with($data->accountIdentifier, $data->email)
            ->andReturnNull();
        $invitationRepository->shouldReceive('save')
            ->once()
            ->with($data->invitation);

        $invitationFactory = Mockery::mock(InvitationFactoryInterface::class);
        $invitationFactory->shouldReceive('create')
            ->once()
            ->with($data->accountIdentifier, $data->inviterIdentityIdentifier, $data->email)
            ->andReturn($data->invitation);

        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);
        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(InvitationFactoryInterface::class, $invitationFactory);

        $useCase = $this->app->make(CreateInvitationInterface::class);
        $output = new CreateInvitationOutput();
        $useCase->process($data->input, $output);

        $this->assertCount(1, $output->toArray());
    }

    /**
     * 異常系: PolicyEvaluatorが拒否したユーザーは招待を作成できないこと
     *
     * @throws BindingResolutionException
     */
    public function testProcessThrowsExceptionWhenPolicyDeniesInvitationCreate(): void
    {
        $this->expectException(DisallowedInvitationException::class);
        $this->expectExceptionMessage('招待を作成する権限がありません。');

        $data = $this->createTestData();
        $this->bindPolicyEvaluator($data, false);

        $this->app->instance(InvitationRepositoryInterface::class, Mockery::mock(InvitationRepositoryInterface::class));
        $this->app->instance(InvitationFactoryInterface::class, Mockery::mock(InvitationFactoryInterface::class));

        $useCase = $this->app->make(CreateInvitationInterface::class);
        $output = new CreateInvitationOutput();
        $useCase->process($data->input, $output);
    }

    /**
     * 正常系: 既存のPENDING状態の招待がある場合は取り消されること
     *
     * @throws BindingResolutionException
     */
    public function testProcessRevokesExistingPendingInvitation(): void
    {
        $data = $this->createTestData();
        $this->bindPolicyEvaluator($data, true);

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(static fn ($event) => $event instanceof InvitationCreated));

        $existingInvitation = Mockery::mock(Invitation::class);
        $existingInvitation->shouldReceive('revoke')->once();

        $invitationRepository = Mockery::mock(InvitationRepositoryInterface::class);
        $invitationRepository->shouldReceive('findPendingByAccountAndEmail')
            ->once()
            ->with($data->accountIdentifier, $data->email)
            ->andReturn($existingInvitation);
        $invitationRepository->shouldReceive('save')
            ->once()
            ->with($existingInvitation);
        $invitationRepository->shouldReceive('save')
            ->once()
            ->with($data->invitation);

        $invitationFactory = Mockery::mock(InvitationFactoryInterface::class);
        $invitationFactory->shouldReceive('create')
            ->once()
            ->with($data->accountIdentifier, $data->inviterIdentityIdentifier, $data->email)
            ->andReturn($data->invitation);

        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);
        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(InvitationFactoryInterface::class, $invitationFactory);

        $useCase = $this->app->make(CreateInvitationInterface::class);
        $output = new CreateInvitationOutput();
        $useCase->process($data->input, $output);

        $this->assertCount(1, $output->toArray());
    }

    /**
     * 正常系: 複数のメールアドレスに招待を作成できること
     *
     * @throws BindingResolutionException
     */
    public function testProcessWithMultipleEmails(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $inviterIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, $inviterIdentityIdentifier, $accountIdentifier);
        $email1 = new Email('user1@example.com');
        $email2 = new Email('user2@example.com');
        $invitation1 = $this->createInvitation($accountIdentifier, $inviterIdentityIdentifier, $email1);
        $invitation2 = $this->createInvitation($accountIdentifier, $inviterIdentityIdentifier, $email2);
        $input = new CreateInvitationInput($accountIdentifier, $inviterIdentityIdentifier, [$email1, $email2]);

        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->once()
            ->with(
                Mockery::on(
                    static fn (Principal $actual): bool => $actual === $principal
                ),
                Action::INVITATION_CREATE,
                Mockery::on(static fn (Resource $resource) => (string) $resource->accountIdentifier() === (string) $accountIdentifier)
            )
            ->andReturnTrue();

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')
            ->once()
            ->with($inviterIdentityIdentifier, $accountIdentifier)
            ->andReturn($principal);

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatch')
            ->times(2)
            ->with(Mockery::on(static fn ($event) => $event instanceof InvitationCreated));

        $invitationRepository = Mockery::mock(InvitationRepositoryInterface::class);
        $invitationRepository->shouldReceive('findPendingByAccountAndEmail')->once()->with($accountIdentifier, $email1)->andReturnNull();
        $invitationRepository->shouldReceive('findPendingByAccountAndEmail')->once()->with($accountIdentifier, $email2)->andReturnNull();
        $invitationRepository->shouldReceive('save')->once()->with($invitation1);
        $invitationRepository->shouldReceive('save')->once()->with($invitation2);

        $invitationFactory = Mockery::mock(InvitationFactoryInterface::class);
        $invitationFactory->shouldReceive('create')->once()->with($accountIdentifier, $inviterIdentityIdentifier, $email1)->andReturn($invitation1);
        $invitationFactory->shouldReceive('create')->once()->with($accountIdentifier, $inviterIdentityIdentifier, $email2)->andReturn($invitation2);

        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);
        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(InvitationFactoryInterface::class, $invitationFactory);

        $useCase = $this->app->make(CreateInvitationInterface::class);
        $output = new CreateInvitationOutput();
        $useCase->process($input, $output);

        $this->assertCount(2, $output->toArray());
    }

    private function bindPolicyEvaluator(CreateInvitationTestData $data, bool $allowed): void
    {
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')
            ->once()
            ->with(
                Mockery::on(
                    static fn (Principal $principal): bool => $principal === $data->principal
                ),
                Action::INVITATION_CREATE,
                Mockery::on(static fn (Resource $resource) => (string) $resource->accountIdentifier() === (string) $data->accountIdentifier)
            )
            ->andReturn($allowed);

        $this->app->instance(PolicyEvaluatorInterface::class, $policyEvaluator);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')
            ->once()
            ->with($data->inviterIdentityIdentifier, $data->accountIdentifier)
            ->andReturn($data->principal);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
    }

    private function createTestData(): CreateInvitationTestData
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $inviterIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($principalIdentifier, $inviterIdentityIdentifier, $accountIdentifier);
        $email = new Email('invitee@example.com');
        $invitation = $this->createInvitation($accountIdentifier, $inviterIdentityIdentifier, $email);
        $input = new CreateInvitationInput($accountIdentifier, $inviterIdentityIdentifier, [$email]);

        return new CreateInvitationTestData(
            $accountIdentifier,
            $inviterIdentityIdentifier,
            $principalIdentifier,
            $principal,
            $email,
            $invitation,
            $input,
        );
    }

    private function createInvitation(
        AccountIdentifier $accountIdentifier,
        IdentityIdentifier $inviterIdentityIdentifier,
        Email $email
    ): Invitation {
        return new Invitation(
            new InvitationIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            $inviterIdentityIdentifier,
            $email,
            new OneTimeToken(bin2hex(random_bytes(32))),
            InvitationStatus::PENDING,
            new DateTimeImmutable('+7 days'),
            null,
            null,
            new DateTimeImmutable(),
        );
    }
}

readonly class CreateInvitationTestData
{
    public function __construct(
        public AccountIdentifier $accountIdentifier,
        public IdentityIdentifier $inviterIdentityIdentifier,
        public PrincipalIdentifier $principalIdentifier,
        public Principal $principal,
        public Email $email,
        public Invitation $invitation,
        public CreateInvitationInput $input,
    ) {
    }
}
