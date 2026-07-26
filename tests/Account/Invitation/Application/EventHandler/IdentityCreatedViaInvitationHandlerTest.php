<?php

declare(strict_types=1);

namespace Tests\Account\Invitation\Application\EventHandler;

use DateTimeImmutable;
use DomainException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Invitation\Application\EventHandler\IdentityCreatedViaInvitationHandler;
use Source\Account\Invitation\Application\Exception\InvitationNotFoundException;
use Source\Account\Invitation\Domain\Entity\Invitation;
use Source\Account\Invitation\Domain\Event\InvitationAccepted;
use Source\Account\Invitation\Domain\Repository\InvitationRepositoryInterface;
use Source\Account\Invitation\Domain\ValueObject\InvitationIdentifier;
use Source\Account\Invitation\Domain\ValueObject\InvitationStatus;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Account\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Identity\Domain\Event\IdentityCreatedViaInvitation;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\OneTimeToken;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class IdentityCreatedViaInvitationHandlerTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作していること
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $invitationRepository = Mockery::mock(InvitationRepositoryInterface::class);
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);

        $handler = $this->app->make(IdentityCreatedViaInvitationHandler::class);

        $this->assertInstanceOf(IdentityCreatedViaInvitationHandler::class, $handler);
    }

    /**
     * 正常系: 既存のMemberグループがある場合、そのグループにメンバーを追加すること
     *
     * @throws BindingResolutionException
     */
    public function testHandleWhenMemberGroupExists(): void
    {
        $data = $this->createTestData();

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(
                static fn ($event) => $event instanceof InvitationAccepted
                    && (string) $event->invitationIdentifier === (string) $data->invitation->invitationIdentifier()
                    && (string) $event->accountIdentifier === (string) $data->accountIdentifier
                    && (string) $event->acceptedByIdentityIdentifier === (string) $data->identityIdentifier
            ));

        $invitationRepository = Mockery::mock(InvitationRepositoryInterface::class);
        $invitationRepository->shouldReceive('findByToken')
            ->once()
            ->with($data->oneTimeToken)
            ->andReturn($data->invitation);
        $invitationRepository->shouldReceive('save')
            ->once()
            ->with($data->invitation);

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByAccountIdAndRole')
            ->once()
            ->with($data->accountIdentifier, $data->basicRole->roleIdentifier())
            ->andReturn($data->memberGroup);
        $principalGroupRepository->shouldReceive('save')
            ->once()
            ->with($data->memberGroup);

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->shouldNotReceive('create');

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('create')
            ->once()
            ->with($data->identityIdentifier, $data->accountIdentifier)
            ->andReturn($data->principal);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('save')
            ->once()
            ->with($data->principal);

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByName')
            ->once()
            ->with(Role::BASIC)
            ->andReturn($data->basicRole);

        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);
        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);

        $handler = $this->app->make(IdentityCreatedViaInvitationHandler::class);
        $handler->handle($data->event);

        $this->assertTrue($data->memberGroup->hasMember($data->principalIdentifier));
    }

    /**
     * 正常系: Memberグループが存在しない場合、新規作成してメンバーを追加すること
     *
     * @throws BindingResolutionException
     */
    public function testHandleWhenMemberGroupNotExists(): void
    {
        $data = $this->createTestData();

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(
                static fn ($event) => $event instanceof InvitationAccepted
            ));

        $invitationRepository = Mockery::mock(InvitationRepositoryInterface::class);
        $invitationRepository->shouldReceive('findByToken')
            ->once()
            ->with($data->oneTimeToken)
            ->andReturn($data->invitation);
        $invitationRepository->shouldReceive('save')
            ->once()
            ->with($data->invitation);

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByAccountIdAndRole')
            ->once()
            ->with($data->accountIdentifier, $data->basicRole->roleIdentifier())
            ->andReturnNull();
        $principalGroupRepository->shouldReceive('save')
            ->once()
            ->with($data->memberGroup);

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->shouldReceive('create')
            ->once()
            ->with($data->accountIdentifier, 'Members', false)
            ->andReturn($data->memberGroup);

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('create')
            ->once()
            ->with($data->identityIdentifier, $data->accountIdentifier)
            ->andReturn($data->principal);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('save')
            ->once()
            ->with($data->principal);

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByName')
            ->once()
            ->with(Role::BASIC)
            ->andReturn($data->basicRole);

        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);
        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);

        $handler = $this->app->make(IdentityCreatedViaInvitationHandler::class);
        $handler->handle($data->event);

        $this->assertTrue($data->memberGroup->hasMember($data->principalIdentifier));
    }

    /**
     * 異常系: 招待が見つからない場合、InvitationNotFoundExceptionがスローされること
     *
     * @throws BindingResolutionException
     */
    public function testHandleThrowsInvitationNotFoundException(): void
    {
        $this->expectException(InvitationNotFoundException::class);
        $this->expectExceptionMessage('招待が見つかりません。');

        $data = $this->createTestData();

        $invitationRepository = Mockery::mock(InvitationRepositoryInterface::class);
        $invitationRepository->shouldReceive('findByToken')
            ->once()
            ->with($data->oneTimeToken)
            ->andReturnNull();

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);
        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);

        $handler = $this->app->make(IdentityCreatedViaInvitationHandler::class);
        $handler->handle($data->event);
    }

    /**
     * 異常系: 招待が受け入れ可能でない場合、DomainExceptionがスローされること
     *
     * @throws BindingResolutionException
     */
    public function testHandleThrowsDomainExceptionWhenInvitationNotAcceptable(): void
    {
        $this->expectException(DomainException::class);

        $data = $this->createTestData();

        $expiredInvitation = Mockery::mock(Invitation::class);
        $expiredInvitation->shouldReceive('assertAcceptable')
            ->once()
            ->andThrow(new DomainException('この招待リンクは有効期限が切れています。'));

        $invitationRepository = Mockery::mock(InvitationRepositoryInterface::class);
        $invitationRepository->shouldReceive('findByToken')
            ->once()
            ->with($data->oneTimeToken)
            ->andReturn($expiredInvitation);

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);
        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);

        $handler = $this->app->make(IdentityCreatedViaInvitationHandler::class);
        $handler->handle($data->event);
    }

    /**
     * 異常系: 招待が既に使用済みの場合、DomainExceptionがスローされること
     *
     * @throws BindingResolutionException
     */
    public function testHandleThrowsDomainExceptionWhenInvitationAlreadyUsed(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('この招待は既に使用済みまたは取り消されています。');

        $data = $this->createTestData();

        $usedInvitation = Mockery::mock(Invitation::class);
        $usedInvitation->shouldReceive('assertAcceptable')
            ->once()
            ->andThrow(new DomainException('この招待は既に使用済みまたは取り消されています。'));

        $invitationRepository = Mockery::mock(InvitationRepositoryInterface::class);
        $invitationRepository->shouldReceive('findByToken')
            ->once()
            ->with($data->oneTimeToken)
            ->andReturn($usedInvitation);

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);
        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);

        $handler = $this->app->make(IdentityCreatedViaInvitationHandler::class);
        $handler->handle($data->event);
    }

    private function createTestData(): IdentityCreatedViaInvitationHandlerTestData
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $oneTimeToken = new OneTimeToken(bin2hex(random_bytes(32)));
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $inviterIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $event = new IdentityCreatedViaInvitation(
            identityIdentifier: $identityIdentifier,
            oneTimeToken: $oneTimeToken,
        );

        $invitation = new Invitation(
            new InvitationIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            $inviterIdentityIdentifier,
            new Email('invitee@example.com'),
            $oneTimeToken,
            InvitationStatus::PENDING,
            new DateTimeImmutable('+7 days'),
            null,
            null,
            new DateTimeImmutable(),
        );
        $principal = new Principal($principalIdentifier, $identityIdentifier, $accountIdentifier);

        $basicRole = new Role(
            new RoleIdentifier(StrTestHelper::generateUuid()),
            Role::BASIC,
            [],
            true,
        );

        $memberGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            'Members',
            false,
            new DateTimeImmutable(),
        );

        return new IdentityCreatedViaInvitationHandlerTestData(
            $identityIdentifier,
            $principalIdentifier,
            $oneTimeToken,
            $accountIdentifier,
            $event,
            $invitation,
            $principal,
            $memberGroup,
            $basicRole,
        );
    }
}

readonly class IdentityCreatedViaInvitationHandlerTestData
{
    public function __construct(
        public IdentityIdentifier $identityIdentifier,
        public PrincipalIdentifier $principalIdentifier,
        public OneTimeToken $oneTimeToken,
        public AccountIdentifier $accountIdentifier,
        public IdentityCreatedViaInvitation $event,
        public Invitation $invitation,
        public Principal $principal,
        public PrincipalGroup $memberGroup,
        public Role $basicRole,
    ) {
    }
}
