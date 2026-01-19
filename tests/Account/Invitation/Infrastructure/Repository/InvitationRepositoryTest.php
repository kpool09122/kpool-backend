<?php

declare(strict_types=1);

namespace Tests\Account\Invitation\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Invitation\Domain\Entity\Invitation;
use Source\Account\Invitation\Domain\Repository\InvitationRepositoryInterface;
use Source\Account\Invitation\Domain\ValueObject\InvitationIdentifier;
use Source\Account\Invitation\Domain\ValueObject\InvitationStatus;
use Source\Account\Invitation\Domain\ValueObject\InvitationToken;
use Source\Account\Invitation\Infrastructure\Repository\InvitationRepository;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class InvitationRepositoryTest extends TestCase
{
    private function createTestInvitation(
        ?string $invitationId = null,
        ?string $accountId = null,
        ?string $inviterIdentityId = null,
        ?string $email = null,
        ?string $token = null,
        InvitationStatus $status = InvitationStatus::PENDING,
        ?DateTimeImmutable $expiresAt = null,
        ?string $acceptedByIdentityId = null,
        ?DateTimeImmutable $acceptedAt = null,
    ): Invitation {
        $invitationId ??= StrTestHelper::generateUuid();
        $accountId ??= StrTestHelper::generateUuid();
        $inviterIdentityId ??= StrTestHelper::generateUuid();
        $email ??= StrTestHelper::generateSmallAlphaStr(10) . '@example.com';
        $token ??= bin2hex(random_bytes(32));
        $expiresAt ??= new DateTimeImmutable('+24 hours');

        // FK制約のためAccountとIdentityを事前に作成
        CreateAccount::create($accountId);
        CreateIdentity::create(
            new IdentityIdentifier($inviterIdentityId),
            ['email' => StrTestHelper::generateSmallAlphaStr(10) . '@example.com']
        );

        if ($acceptedByIdentityId !== null) {
            CreateIdentity::create(
                new IdentityIdentifier($acceptedByIdentityId),
                ['email' => StrTestHelper::generateSmallAlphaStr(10) . '@example.com']
            );
        }

        return new Invitation(
            new InvitationIdentifier($invitationId),
            new AccountIdentifier($accountId),
            new IdentityIdentifier($inviterIdentityId),
            new Email($email),
            new InvitationToken($token),
            $status,
            $expiresAt,
            $acceptedByIdentityId !== null ? new IdentityIdentifier($acceptedByIdentityId) : null,
            $acceptedAt,
            new DateTimeImmutable(),
        );
    }

    /**
     * 正常系: DIが正しく動作すること
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = $this->app->make(InvitationRepositoryInterface::class);
        $this->assertInstanceOf(InvitationRepository::class, $repository);
    }

    /**
     * 正常系: 正しくInvitationを保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $invitationId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $inviterIdentityId = StrTestHelper::generateUuid();
        $email = 'invitee@example.com';
        $token = bin2hex(random_bytes(32));

        $invitation = $this->createTestInvitation(
            invitationId: $invitationId,
            accountId: $accountId,
            inviterIdentityId: $inviterIdentityId,
            email: $email,
            token: $token,
        );

        $repository = $this->app->make(InvitationRepositoryInterface::class);
        $repository->save($invitation);

        $this->assertDatabaseHas('invitations', [
            'id' => $invitationId,
            'account_id' => $accountId,
            'invited_by_identity_id' => $inviterIdentityId,
            'email' => $email,
            'token' => $token,
            'status' => 'pending',
        ]);
    }

    /**
     * 正常系: 受け入れ済みのInvitationを保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithAcceptedInvitation(): void
    {
        $invitationId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $inviterIdentityId = StrTestHelper::generateUuid();
        $acceptedByIdentityId = StrTestHelper::generateUuid();
        $acceptedAt = new DateTimeImmutable();

        $invitation = $this->createTestInvitation(
            invitationId: $invitationId,
            accountId: $accountId,
            inviterIdentityId: $inviterIdentityId,
            status: InvitationStatus::ACCEPTED,
            acceptedByIdentityId: $acceptedByIdentityId,
            acceptedAt: $acceptedAt,
        );

        $repository = $this->app->make(InvitationRepositoryInterface::class);
        $repository->save($invitation);

        $this->assertDatabaseHas('invitations', [
            'id' => $invitationId,
            'status' => 'accepted',
            'accepted_by_identity_id' => $acceptedByIdentityId,
        ]);
    }

    /**
     * 正常系: 既存のInvitationを更新できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveUpdatesExistingInvitation(): void
    {
        $invitationId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $inviterIdentityId = StrTestHelper::generateUuid();
        $acceptedByIdentityId = StrTestHelper::generateUuid();

        // 最初にPENDINGで保存
        $invitation = $this->createTestInvitation(
            invitationId: $invitationId,
            accountId: $accountId,
            inviterIdentityId: $inviterIdentityId,
            status: InvitationStatus::PENDING,
        );

        $repository = $this->app->make(InvitationRepositoryInterface::class);
        $repository->save($invitation);

        $this->assertDatabaseHas('invitations', [
            'id' => $invitationId,
            'status' => 'pending',
        ]);

        // ACCEPTEDに更新
        CreateIdentity::create(
            new IdentityIdentifier($acceptedByIdentityId),
            ['email' => StrTestHelper::generateSmallAlphaStr(10) . '@example.com']
        );

        $acceptedAt = new DateTimeImmutable();
        $updatedInvitation = new Invitation(
            new InvitationIdentifier($invitationId),
            new AccountIdentifier($accountId),
            new IdentityIdentifier($inviterIdentityId),
            $invitation->email(),
            $invitation->token(),
            InvitationStatus::ACCEPTED,
            $invitation->expiresAt(),
            new IdentityIdentifier($acceptedByIdentityId),
            $acceptedAt,
            $invitation->createdAt(),
        );

        $repository->save($updatedInvitation);

        $this->assertDatabaseHas('invitations', [
            'id' => $invitationId,
            'status' => 'accepted',
            'accepted_by_identity_id' => $acceptedByIdentityId,
        ]);
    }

    /**
     * 正常系: トークンでInvitationを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByToken(): void
    {
        $invitationId = StrTestHelper::generateUuid();
        $token = bin2hex(random_bytes(32));

        $invitation = $this->createTestInvitation(
            invitationId: $invitationId,
            token: $token,
        );

        $repository = $this->app->make(InvitationRepositoryInterface::class);
        $repository->save($invitation);

        $result = $repository->findByToken(new InvitationToken($token));

        $this->assertNotNull($result);
        $this->assertSame($invitationId, (string) $result->invitationIdentifier());
        $this->assertSame($token, (string) $result->token());
    }

    /**
     * 正常系: トークンが存在しない場合はnullを返すこと
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTokenWhenNotFound(): void
    {
        $repository = $this->app->make(InvitationRepositoryInterface::class);
        $result = $repository->findByToken(new InvitationToken(bin2hex(random_bytes(32))));

        $this->assertNull($result);
    }

    /**
     * 正常系: AccountとEmailでPENDINGのInvitationを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindPendingByAccountAndEmail(): void
    {
        $invitationId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $email = 'pending@example.com';

        $invitation = $this->createTestInvitation(
            invitationId: $invitationId,
            accountId: $accountId,
            email: $email,
            status: InvitationStatus::PENDING,
        );

        $repository = $this->app->make(InvitationRepositoryInterface::class);
        $repository->save($invitation);

        $result = $repository->findPendingByAccountAndEmail(
            new AccountIdentifier($accountId),
            new Email($email)
        );

        $this->assertNotNull($result);
        $this->assertSame($invitationId, (string) $result->invitationIdentifier());
        $this->assertSame($email, (string) $result->email());
        $this->assertSame(InvitationStatus::PENDING, $result->status());
    }

    /**
     * 正常系: AccountとEmailでInvitationが存在しない場合はnullを返すこと
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindPendingByAccountAndEmailWhenNotFound(): void
    {
        $repository = $this->app->make(InvitationRepositoryInterface::class);
        $result = $repository->findPendingByAccountAndEmail(
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new Email('notfound@example.com')
        );

        $this->assertNull($result);
    }

    /**
     * 正常系: PENDING以外のステータスは無視されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindPendingByAccountAndEmailIgnoresNonPending(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $email = 'test@example.com';
        $acceptedByIdentityId = StrTestHelper::generateUuid();

        // ACCEPTEDステータスの招待を作成
        $acceptedInvitation = $this->createTestInvitation(
            accountId: $accountId,
            email: $email,
            status: InvitationStatus::ACCEPTED,
            acceptedByIdentityId: $acceptedByIdentityId,
            acceptedAt: new DateTimeImmutable(),
        );

        $repository = $this->app->make(InvitationRepositoryInterface::class);
        $repository->save($acceptedInvitation);

        $result = $repository->findPendingByAccountAndEmail(
            new AccountIdentifier($accountId),
            new Email($email)
        );

        $this->assertNull($result);
    }

    /**
     * 正常系: REVOKEDステータスも無視されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindPendingByAccountAndEmailIgnoresRevoked(): void
    {
        $accountId = StrTestHelper::generateUuid();
        $email = 'revoked@example.com';

        // REVOKEDステータスの招待を作成
        $revokedInvitation = $this->createTestInvitation(
            accountId: $accountId,
            email: $email,
            status: InvitationStatus::REVOKED,
        );

        $repository = $this->app->make(InvitationRepositoryInterface::class);
        $repository->save($revokedInvitation);

        $result = $repository->findPendingByAccountAndEmail(
            new AccountIdentifier($accountId),
            new Email($email)
        );

        $this->assertNull($result);
    }

    /**
     * 正常系: 取得したInvitationのすべてのプロパティが正しくマッピングされること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTokenMapsAllProperties(): void
    {
        $invitationId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $inviterIdentityId = StrTestHelper::generateUuid();
        $email = 'fulltest@example.com';
        $token = bin2hex(random_bytes(32));
        $expiresAt = new DateTimeImmutable('+48 hours');

        $invitation = $this->createTestInvitation(
            invitationId: $invitationId,
            accountId: $accountId,
            inviterIdentityId: $inviterIdentityId,
            email: $email,
            token: $token,
            status: InvitationStatus::PENDING,
            expiresAt: $expiresAt,
        );

        $repository = $this->app->make(InvitationRepositoryInterface::class);
        $repository->save($invitation);

        $result = $repository->findByToken(new InvitationToken($token));

        $this->assertNotNull($result);
        $this->assertSame($invitationId, (string) $result->invitationIdentifier());
        $this->assertSame($accountId, (string) $result->accountIdentifier());
        $this->assertSame($inviterIdentityId, (string) $result->invitedByIdentityIdentifier());
        $this->assertSame($email, (string) $result->email());
        $this->assertSame($token, (string) $result->token());
        $this->assertSame(InvitationStatus::PENDING, $result->status());
        $this->assertSame($expiresAt->format('Y-m-d H:i'), $result->expiresAt()->format('Y-m-d H:i'));
        $this->assertNull($result->acceptedByIdentityIdentifier());
        $this->assertNull($result->acceptedAt());
        $this->assertNotNull($result->createdAt());
    }

    /**
     * 正常系: 受け入れ済みInvitationのaccepted_by_identity_idとaccepted_atが正しくマッピングされること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTokenMapsAcceptedProperties(): void
    {
        $invitationId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $inviterIdentityId = StrTestHelper::generateUuid();
        $acceptedByIdentityId = StrTestHelper::generateUuid();
        $token = bin2hex(random_bytes(32));
        $acceptedAt = new DateTimeImmutable();

        $invitation = $this->createTestInvitation(
            invitationId: $invitationId,
            accountId: $accountId,
            inviterIdentityId: $inviterIdentityId,
            token: $token,
            status: InvitationStatus::ACCEPTED,
            acceptedByIdentityId: $acceptedByIdentityId,
            acceptedAt: $acceptedAt,
        );

        $repository = $this->app->make(InvitationRepositoryInterface::class);
        $repository->save($invitation);

        $result = $repository->findByToken(new InvitationToken($token));

        $this->assertNotNull($result);
        $this->assertSame($invitationId, (string) $result->invitationIdentifier());
        $this->assertSame(InvitationStatus::ACCEPTED, $result->status());
        $this->assertNotNull($result->acceptedByIdentityIdentifier());
        $this->assertSame($acceptedByIdentityId, (string) $result->acceptedByIdentityIdentifier());
        $this->assertNotNull($result->acceptedAt());
        $this->assertSame($acceptedAt->format('Y-m-d H:i'), $result->acceptedAt()->format('Y-m-d H:i'));
    }
}
