<?php

declare(strict_types=1);

namespace Tests\Account\Invitation\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Invitation\Domain\Entity\Invitation;
use Source\Account\Invitation\Domain\Exception\InvitationAlreadyUsedOrRevokedException;
use Source\Account\Invitation\Domain\Exception\InvitationExpiredException;
use Source\Account\Invitation\Domain\Exception\InvitationNotPendingException;
use Source\Account\Invitation\Domain\ValueObject\InvitationIdentifier;
use Source\Account\Invitation\Domain\ValueObject\InvitationStatus;
use Source\Account\Invitation\Domain\ValueObject\InvitationToken;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class InvitationTest extends TestCase
{
    public function test__construct(): void
    {
        $invitationIdentifier = new InvitationIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $invitedByIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $email = new Email('test@example.com');
        $token = new InvitationToken(StrTestHelper::generateHex(64));
        $status = InvitationStatus::PENDING;
        $expiresAt = new DateTimeImmutable('+7 days');
        $createdAt = new DateTimeImmutable();

        $invitation = new Invitation(
            $invitationIdentifier,
            $accountIdentifier,
            $invitedByIdentityIdentifier,
            $email,
            $token,
            $status,
            $expiresAt,
            null,
            null,
            $createdAt,
        );

        $this->assertSame($invitationIdentifier, $invitation->invitationIdentifier());
        $this->assertSame($accountIdentifier, $invitation->accountIdentifier());
        $this->assertSame($invitedByIdentityIdentifier, $invitation->invitedByIdentityIdentifier());
        $this->assertSame($email, $invitation->email());
        $this->assertSame($token, $invitation->token());
        $this->assertSame($status, $invitation->status());
        $this->assertSame($expiresAt, $invitation->expiresAt());
        $this->assertNull($invitation->acceptedByIdentityIdentifier());
        $this->assertNull($invitation->acceptedAt());
        $this->assertSame($createdAt, $invitation->createdAt());
    }

    public function testIsExpiredReturnsTrueWhenExpired(): void
    {
        $invitation = $this->createInvitation(expiresAt: new DateTimeImmutable('-1 day'));

        $this->assertTrue($invitation->isExpired());
    }

    public function testIsExpiredReturnsFalseWhenNotExpired(): void
    {
        $invitation = $this->createInvitation();

        $this->assertFalse($invitation->isExpired());
    }

    public function testIsPending(): void
    {
        $invitation = $this->createInvitation();

        $this->assertTrue($invitation->isPending());
    }

    public function testIsPendingReturnsFalseWhenAccepted(): void
    {
        $invitation = $this->createInvitation(InvitationStatus::ACCEPTED);

        $this->assertFalse($invitation->isPending());
    }

    public function testAssertAcceptableThrowsWhenNotPending(): void
    {
        $invitation = $this->createInvitation(InvitationStatus::ACCEPTED);

        $this->expectException(InvitationAlreadyUsedOrRevokedException::class);

        $invitation->assertAcceptable();
    }

    public function testAssertAcceptableThrowsWhenRevoked(): void
    {
        $invitation = $this->createInvitation(InvitationStatus::REVOKED);

        $this->expectException(InvitationAlreadyUsedOrRevokedException::class);

        $invitation->assertAcceptable();
    }

    public function testAssertAcceptableThrowsWhenExpired(): void
    {
        $invitation = $this->createInvitation(expiresAt: new DateTimeImmutable('-1 day'));

        $this->expectException(InvitationExpiredException::class);

        $invitation->assertAcceptable();
    }

    public function testAccept(): void
    {
        $invitation = $this->createInvitation();
        $acceptedByIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $invitation->accept($acceptedByIdentityIdentifier);

        $this->assertSame(InvitationStatus::ACCEPTED, $invitation->status());
        $this->assertSame($acceptedByIdentityIdentifier, $invitation->acceptedByIdentityIdentifier());
        $this->assertNotNull($invitation->acceptedAt());
    }

    public function testAcceptThrowsWhenNotPending(): void
    {
        $invitation = $this->createInvitation(InvitationStatus::ACCEPTED);
        $acceptedByIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $this->expectException(InvitationAlreadyUsedOrRevokedException::class);

        $invitation->accept($acceptedByIdentityIdentifier);
    }

    public function testAcceptThrowsWhenExpired(): void
    {
        $invitation = $this->createInvitation(expiresAt: new DateTimeImmutable('-1 day'));
        $acceptedByIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $this->expectException(InvitationExpiredException::class);

        $invitation->accept($acceptedByIdentityIdentifier);
    }

    public function testRevoke(): void
    {
        $invitation = $this->createInvitation();

        $invitation->revoke();

        $this->assertSame(InvitationStatus::REVOKED, $invitation->status());
    }

    public function testRevokeThrowsWhenAccepted(): void
    {
        $invitation = $this->createInvitation(InvitationStatus::ACCEPTED);

        $this->expectException(InvitationNotPendingException::class);

        $invitation->revoke();
    }

    public function testRevokeThrowsWhenAlreadyRevoked(): void
    {
        $invitation = $this->createInvitation(InvitationStatus::REVOKED);

        $this->expectException(InvitationNotPendingException::class);

        $invitation->revoke();
    }

    private function createInvitation(
        InvitationStatus $status = InvitationStatus::PENDING,
        ?DateTimeImmutable $expiresAt = null,
    ): Invitation {
        $expiresAt ??= new DateTimeImmutable('+7 days');
        $acceptedByIdentityIdentifier = $status === InvitationStatus::ACCEPTED
            ? new IdentityIdentifier(StrTestHelper::generateUuid())
            : null;
        $acceptedAt = $status === InvitationStatus::ACCEPTED
            ? new DateTimeImmutable()
            : null;

        return new Invitation(
            new InvitationIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new Email('test@example.com'),
            new InvitationToken(StrTestHelper::generateHex(64)),
            $status,
            $expiresAt,
            $acceptedByIdentityIdentifier,
            $acceptedAt,
            new DateTimeImmutable(),
        );
    }
}
