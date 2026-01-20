<?php

declare(strict_types=1);

namespace Tests\Account\Invitation\Application\UseCase\Command\CreateInvitation;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Event;
use Mockery;
use Source\Account\IdentityGroup\Domain\Entity\IdentityGroup;
use Source\Account\IdentityGroup\Domain\Repository\IdentityGroupRepositoryInterface;
use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;
use Source\Account\Invitation\Application\Exception\DisallowedInvitationException;
use Source\Account\Invitation\Application\UseCase\Command\CreateInvitation\CreateInvitation;
use Source\Account\Invitation\Application\UseCase\Command\CreateInvitation\CreateInvitationInput;
use Source\Account\Invitation\Application\UseCase\Command\CreateInvitation\CreateInvitationInterface;
use Source\Account\Invitation\Domain\Entity\Invitation;
use Source\Account\Invitation\Domain\Event\InvitationCreated;
use Source\Account\Invitation\Domain\Factory\InvitationFactoryInterface;
use Source\Account\Invitation\Domain\Repository\InvitationRepositoryInterface;
use Source\Account\Invitation\Domain\ValueObject\InvitationIdentifier;
use Source\Account\Invitation\Domain\ValueObject\InvitationStatus;
use Source\Account\Invitation\Domain\ValueObject\InvitationToken;
use Source\Account\Shared\Domain\ValueObject\IdentityGroupIdentifier;
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
        $invitationRepository = Mockery::mock(InvitationRepositoryInterface::class);
        $invitationFactory = Mockery::mock(InvitationFactoryInterface::class);
        $identityGroupRepository = Mockery::mock(IdentityGroupRepositoryInterface::class);

        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(InvitationFactoryInterface::class, $invitationFactory);
        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);

        $useCase = $this->app->make(CreateInvitationInterface::class);

        $this->assertInstanceOf(CreateInvitation::class, $useCase);
    }

    /**
     * 正常系: OWNER権限のユーザーが招待を作成できること
     *
     * @throws BindingResolutionException
     */
    public function testProcessWithOwnerRole(): void
    {
        Event::fake();

        $data = $this->createTestData(AccountRole::OWNER);

        $identityGroupRepository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $identityGroupRepository->shouldReceive('findByAccountIdAndIdentityId')
            ->once()
            ->with($data->accountIdentifier, $data->inviterIdentityIdentifier)
            ->andReturn([$data->identityGroup]);

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

        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);
        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(InvitationFactoryInterface::class, $invitationFactory);

        $useCase = $this->app->make(CreateInvitationInterface::class);
        $result = $useCase->process($data->input);

        $this->assertCount(1, $result);
        $this->assertSame($data->invitation, $result[0]);

        Event::assertDispatched(InvitationCreated::class, static function (InvitationCreated $event) use ($data) {
            return (string) $event->invitationIdentifier === (string) $data->invitation->invitationIdentifier()
                && (string) $event->accountIdentifier === (string) $data->accountIdentifier
                && (string) $event->email === (string) $data->email;
        });
    }

    /**
     * 正常系: ADMIN権限のユーザーが招待を作成できること
     *
     * @throws BindingResolutionException
     */
    public function testProcessWithAdminRole(): void
    {
        Event::fake();

        $data = $this->createTestData(AccountRole::ADMIN);

        $identityGroupRepository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $identityGroupRepository->shouldReceive('findByAccountIdAndIdentityId')
            ->once()
            ->with($data->accountIdentifier, $data->inviterIdentityIdentifier)
            ->andReturn([$data->identityGroup]);

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

        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);
        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(InvitationFactoryInterface::class, $invitationFactory);

        $useCase = $this->app->make(CreateInvitationInterface::class);
        $result = $useCase->process($data->input);

        $this->assertCount(1, $result);
        $this->assertSame($data->invitation, $result[0]);
    }

    /**
     * 異常系: MEMBER権限のユーザーは招待を作成できないこと
     *
     * @throws BindingResolutionException
     */
    public function testProcessThrowsExceptionWhenMemberRole(): void
    {
        $this->expectException(DisallowedInvitationException::class);
        $this->expectExceptionMessage('招待を作成する権限がありません。');

        $data = $this->createTestData(AccountRole::MEMBER);

        $identityGroupRepository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $identityGroupRepository->shouldReceive('findByAccountIdAndIdentityId')
            ->once()
            ->with($data->accountIdentifier, $data->inviterIdentityIdentifier)
            ->andReturn([$data->identityGroup]);

        $invitationRepository = Mockery::mock(InvitationRepositoryInterface::class);
        $invitationFactory = Mockery::mock(InvitationFactoryInterface::class);

        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);
        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(InvitationFactoryInterface::class, $invitationFactory);

        $useCase = $this->app->make(CreateInvitationInterface::class);
        $useCase->process($data->input);
    }

    /**
     * 異常系: アカウントに所属していないユーザーは招待を作成できないこと
     *
     * @throws BindingResolutionException
     */
    public function testProcessThrowsExceptionWhenNotInAccount(): void
    {
        $this->expectException(DisallowedInvitationException::class);
        $this->expectExceptionMessage('招待を作成する権限がありません。');

        $data = $this->createTestData(AccountRole::OWNER);

        $identityGroupRepository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $identityGroupRepository->shouldReceive('findByAccountIdAndIdentityId')
            ->once()
            ->with($data->accountIdentifier, $data->inviterIdentityIdentifier)
            ->andReturn([]);

        $invitationRepository = Mockery::mock(InvitationRepositoryInterface::class);
        $invitationFactory = Mockery::mock(InvitationFactoryInterface::class);

        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);
        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(InvitationFactoryInterface::class, $invitationFactory);

        $useCase = $this->app->make(CreateInvitationInterface::class);
        $useCase->process($data->input);
    }

    /**
     * 正常系: 既存のPENDING状態の招待がある場合は取り消されること
     *
     * @throws BindingResolutionException
     */
    public function testProcessRevokesExistingPendingInvitation(): void
    {
        Event::fake();

        $data = $this->createTestData(AccountRole::OWNER);

        $existingInvitation = Mockery::mock(Invitation::class);
        $existingInvitation->shouldReceive('revoke')->once();

        $identityGroupRepository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $identityGroupRepository->shouldReceive('findByAccountIdAndIdentityId')
            ->once()
            ->with($data->accountIdentifier, $data->inviterIdentityIdentifier)
            ->andReturn([$data->identityGroup]);

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

        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);
        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(InvitationFactoryInterface::class, $invitationFactory);

        $useCase = $this->app->make(CreateInvitationInterface::class);
        $result = $useCase->process($data->input);

        $this->assertCount(1, $result);
    }

    /**
     * 正常系: 複数のメールアドレスに招待を作成できること
     *
     * @throws BindingResolutionException
     */
    public function testProcessWithMultipleEmails(): void
    {
        Event::fake();

        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $inviterIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $email1 = new Email('user1@example.com');
        $email2 = new Email('user2@example.com');

        $identityGroup = $this->createIdentityGroup($accountIdentifier, $inviterIdentityIdentifier, AccountRole::OWNER);

        $invitation1 = $this->createInvitation($accountIdentifier, $inviterIdentityIdentifier, $email1);
        $invitation2 = $this->createInvitation($accountIdentifier, $inviterIdentityIdentifier, $email2);

        $input = new CreateInvitationInput(
            $accountIdentifier,
            $inviterIdentityIdentifier,
            [$email1, $email2]
        );

        $identityGroupRepository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $identityGroupRepository->shouldReceive('findByAccountIdAndIdentityId')
            ->once()
            ->with($accountIdentifier, $inviterIdentityIdentifier)
            ->andReturn([$identityGroup]);

        $invitationRepository = Mockery::mock(InvitationRepositoryInterface::class);
        $invitationRepository->shouldReceive('findPendingByAccountAndEmail')
            ->once()
            ->with($accountIdentifier, $email1)
            ->andReturnNull();
        $invitationRepository->shouldReceive('findPendingByAccountAndEmail')
            ->once()
            ->with($accountIdentifier, $email2)
            ->andReturnNull();
        $invitationRepository->shouldReceive('save')
            ->once()
            ->with($invitation1);
        $invitationRepository->shouldReceive('save')
            ->once()
            ->with($invitation2);

        $invitationFactory = Mockery::mock(InvitationFactoryInterface::class);
        $invitationFactory->shouldReceive('create')
            ->once()
            ->with($accountIdentifier, $inviterIdentityIdentifier, $email1)
            ->andReturn($invitation1);
        $invitationFactory->shouldReceive('create')
            ->once()
            ->with($accountIdentifier, $inviterIdentityIdentifier, $email2)
            ->andReturn($invitation2);

        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);
        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(InvitationFactoryInterface::class, $invitationFactory);

        $useCase = $this->app->make(CreateInvitationInterface::class);
        $result = $useCase->process($input);

        $this->assertCount(2, $result);
        $this->assertSame($invitation1, $result[0]);
        $this->assertSame($invitation2, $result[1]);

        Event::assertDispatchedTimes(InvitationCreated::class, 2);
    }

    /**
     * 正常系: 複数のグループに所属していて、いずれかがOWNER/ADMINであれば招待を作成できること
     *
     * @throws BindingResolutionException
     */
    public function testProcessWithMultipleGroupsOneIsOwner(): void
    {
        Event::fake();

        $data = $this->createTestData(AccountRole::MEMBER);

        $ownerGroup = $this->createIdentityGroup(
            $data->accountIdentifier,
            $data->inviterIdentityIdentifier,
            AccountRole::OWNER
        );

        $identityGroupRepository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $identityGroupRepository->shouldReceive('findByAccountIdAndIdentityId')
            ->once()
            ->with($data->accountIdentifier, $data->inviterIdentityIdentifier)
            ->andReturn([$data->identityGroup, $ownerGroup]);

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

        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);
        $this->app->instance(InvitationRepositoryInterface::class, $invitationRepository);
        $this->app->instance(InvitationFactoryInterface::class, $invitationFactory);

        $useCase = $this->app->make(CreateInvitationInterface::class);
        $result = $useCase->process($data->input);

        $this->assertCount(1, $result);
    }

    private function createTestData(AccountRole $role): CreateInvitationTestData
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $inviterIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $email = new Email('invitee@example.com');

        $identityGroup = $this->createIdentityGroup($accountIdentifier, $inviterIdentityIdentifier, $role);
        $invitation = $this->createInvitation($accountIdentifier, $inviterIdentityIdentifier, $email);

        $input = new CreateInvitationInput(
            $accountIdentifier,
            $inviterIdentityIdentifier,
            [$email]
        );

        return new CreateInvitationTestData(
            $accountIdentifier,
            $inviterIdentityIdentifier,
            $email,
            $identityGroup,
            $invitation,
            $input
        );
    }

    private function createIdentityGroup(
        AccountIdentifier $accountIdentifier,
        IdentityIdentifier $identityIdentifier,
        AccountRole $role
    ): IdentityGroup {
        $identityGroup = new IdentityGroup(
            new IdentityGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            'Test Group',
            $role,
            true,
            new DateTimeImmutable(),
        );
        $identityGroup->addMember($identityIdentifier);

        return $identityGroup;
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
            new InvitationToken(bin2hex(random_bytes(32))),
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
        public Email $email,
        public IdentityGroup $identityGroup,
        public Invitation $invitation,
        public CreateInvitationInput $input,
    ) {
    }
}
