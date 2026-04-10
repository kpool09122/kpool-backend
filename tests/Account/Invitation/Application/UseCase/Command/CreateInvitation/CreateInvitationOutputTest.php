<?php

declare(strict_types=1);

namespace Tests\Account\Invitation\Application\UseCase\Command\CreateInvitation;

use DateTimeImmutable;
use Source\Account\Invitation\Application\UseCase\Command\CreateInvitation\CreateInvitationOutput;
use Source\Account\Invitation\Domain\Entity\Invitation;
use Source\Account\Invitation\Domain\ValueObject\InvitationIdentifier;
use Source\Account\Invitation\Domain\ValueObject\InvitationStatus;
use Source\Account\Invitation\Domain\ValueObject\InvitationToken;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreateInvitationOutputTest extends TestCase
{
    /**
     * 正常系: InvitationsがセットされるとtoArrayが正しい値を返すこと.
     */
    public function testToArrayWithInvitations(): void
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

        $output = new CreateInvitationOutput();
        $output->setInvitations([$invitation]);

        $result = $output->toArray();

        $this->assertCount(1, $result);
        $this->assertSame((string) $invitationIdentifier, $result[0]['invitationIdentifier']);
        $this->assertSame((string) $accountIdentifier, $result[0]['accountIdentifier']);
        $this->assertSame((string) $invitedByIdentityIdentifier, $result[0]['invitedByIdentityIdentifier']);
        $this->assertSame((string) $email, $result[0]['email']);
        $this->assertSame((string) $token, $result[0]['token']);
        $this->assertSame($status->value, $result[0]['status']);
        $this->assertSame($expiresAt->format('Y-m-d\TH:i:sP'), $result[0]['expiresAt']);
        $this->assertSame($createdAt->format('Y-m-d\TH:i:sP'), $result[0]['createdAt']);
    }

    /**
     * 正常系: Invitationsが未セットの場合toArrayが空配列を返すこと.
     */
    public function testToArrayWithoutInvitations(): void
    {
        $output = new CreateInvitationOutput();
        $this->assertSame([], $output->toArray());
    }
}
