<?php

declare(strict_types=1);

namespace Tests\Account\Invitation\Application\EventHandler;

use DateTimeImmutable;
use DomainException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Event;
use Mockery;
use Source\Account\IdentityGroup\Domain\Entity\IdentityGroup;
use Source\Account\IdentityGroup\Domain\Factory\IdentityGroupFactoryInterface;
use Source\Account\IdentityGroup\Domain\Repository\IdentityGroupRepositoryInterface;
use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;
use Source\Account\Invitation\Application\EventHandler\IdentityCreatedViaInvitationHandler;
use Source\Account\Invitation\Application\Exception\InvitationNotFoundException;
use Source\Account\Invitation\Domain\Entity\Invitation;
use Source\Account\Invitation\Domain\Event\InvitationAccepted;
use Source\Account\Invitation\Domain\Repository\InvitationRepositoryInterface;
use Source\Account\Invitation\Domain\ValueObject\InvitationIdentifier;
use Source\Account\Invitation\Domain\ValueObject\InvitationStatus;
use Source\Account\Invitation\Domain\ValueObject\InvitationToken;
use Source\Account\Shared\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Identity\Domain\Event\IdentityCreatedViaInvitation;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
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
        $identityGroupRepository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $identityGroupFactory = Mockery::mock(IdentityGroupFactoryInterface::class);

        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);
        $this->app->instance(IdentityGroupFactoryInterface::class, $identityGroupFactory);

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
        Event::fake();

        $data = $this->createTestData();

        $invitationRepository = Mockery::mock(InvitationRepositoryInterface::class);
        $invitationRepository->shouldReceive('findByToken')
            ->once()
            ->with($data->invitationToken)
            ->andReturn($data->invitation);
        $invitationRepository->shouldReceive('save')
            ->once()
            ->with($data->invitation);

        $identityGroupRepository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $identityGroupRepository->shouldReceive('findByAccountIdAndRole')
            ->once()
            ->with($data->accountIdentifier, AccountRole::MEMBER)
            ->andReturn($data->memberGroup);
        $identityGroupRepository->shouldReceive('save')
            ->once()
            ->with($data->memberGroup);

        $identityGroupFactory = Mockery::mock(IdentityGroupFactoryInterface::class);
        $identityGroupFactory->shouldNotReceive('create');

        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);
        $this->app->instance(IdentityGroupFactoryInterface::class, $identityGroupFactory);

        $handler = $this->app->make(IdentityCreatedViaInvitationHandler::class);
        $handler->handle($data->event);

        $this->assertTrue($data->memberGroup->hasMember($data->identityIdentifier));

        Event::assertDispatched(InvitationAccepted::class, static function (InvitationAccepted $event) use ($data) {
            return (string) $event->invitationIdentifier === (string) $data->invitation->invitationIdentifier()
                && (string) $event->accountIdentifier === (string) $data->accountIdentifier
                && (string) $event->acceptedByIdentityIdentifier === (string) $data->identityIdentifier;
        });
    }

    /**
     * 正常系: Memberグループが存在しない場合、新規作成してメンバーを追加すること
     *
     * @throws BindingResolutionException
     */
    public function testHandleWhenMemberGroupNotExists(): void
    {
        Event::fake();

        $data = $this->createTestData();

        $invitationRepository = Mockery::mock(InvitationRepositoryInterface::class);
        $invitationRepository->shouldReceive('findByToken')
            ->once()
            ->with($data->invitationToken)
            ->andReturn($data->invitation);
        $invitationRepository->shouldReceive('save')
            ->once()
            ->with($data->invitation);

        $identityGroupRepository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $identityGroupRepository->shouldReceive('findByAccountIdAndRole')
            ->once()
            ->with($data->accountIdentifier, AccountRole::MEMBER)
            ->andReturnNull();
        $identityGroupRepository->shouldReceive('save')
            ->once()
            ->with($data->memberGroup);

        $identityGroupFactory = Mockery::mock(IdentityGroupFactoryInterface::class);
        $identityGroupFactory->shouldReceive('create')
            ->once()
            ->with($data->accountIdentifier, 'Members', AccountRole::MEMBER, false)
            ->andReturn($data->memberGroup);

        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);
        $this->app->instance(IdentityGroupFactoryInterface::class, $identityGroupFactory);

        $handler = $this->app->make(IdentityCreatedViaInvitationHandler::class);
        $handler->handle($data->event);

        $this->assertTrue($data->memberGroup->hasMember($data->identityIdentifier));

        Event::assertDispatched(InvitationAccepted::class);
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
            ->with($data->invitationToken)
            ->andReturnNull();

        $identityGroupRepository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $identityGroupFactory = Mockery::mock(IdentityGroupFactoryInterface::class);

        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);
        $this->app->instance(IdentityGroupFactoryInterface::class, $identityGroupFactory);

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
            ->with($data->invitationToken)
            ->andReturn($expiredInvitation);

        $identityGroupRepository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $identityGroupFactory = Mockery::mock(IdentityGroupFactoryInterface::class);

        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);
        $this->app->instance(IdentityGroupFactoryInterface::class, $identityGroupFactory);

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
            ->with($data->invitationToken)
            ->andReturn($usedInvitation);

        $identityGroupRepository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $identityGroupFactory = Mockery::mock(IdentityGroupFactoryInterface::class);

        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);
        $this->app->instance(IdentityGroupFactoryInterface::class, $identityGroupFactory);

        $handler = $this->app->make(IdentityCreatedViaInvitationHandler::class);
        $handler->handle($data->event);
    }

    private function createTestData(): IdentityCreatedViaInvitationHandlerTestData
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $invitationToken = new InvitationToken(bin2hex(random_bytes(32)));
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $inviterIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $event = new IdentityCreatedViaInvitation(
            identityIdentifier: $identityIdentifier,
            invitationToken: $invitationToken,
        );

        $invitation = new Invitation(
            new InvitationIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            $inviterIdentityIdentifier,
            new Email('invitee@example.com'),
            $invitationToken,
            InvitationStatus::PENDING,
            new DateTimeImmutable('+7 days'),
            null,
            null,
            new DateTimeImmutable(),
        );

        $memberGroup = new IdentityGroup(
            new IdentityGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            'Members',
            AccountRole::MEMBER,
            false,
            new DateTimeImmutable(),
        );

        return new IdentityCreatedViaInvitationHandlerTestData(
            $identityIdentifier,
            $invitationToken,
            $accountIdentifier,
            $event,
            $invitation,
            $memberGroup,
        );
    }
}

readonly class IdentityCreatedViaInvitationHandlerTestData
{
    public function __construct(
        public IdentityIdentifier $identityIdentifier,
        public InvitationToken $invitationToken,
        public AccountIdentifier $accountIdentifier,
        public IdentityCreatedViaInvitation $event,
        public Invitation $invitation,
        public IdentityGroup $memberGroup,
    ) {
    }
}
