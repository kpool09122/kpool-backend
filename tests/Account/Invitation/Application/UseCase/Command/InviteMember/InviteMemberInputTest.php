<?php

declare(strict_types=1);

namespace Tests\Account\Invitation\Application\UseCase\Command\InviteMember;

use PHPUnit\Framework\TestCase;
use Source\Account\Invitation\Application\UseCase\Command\InviteMember\InviteMemberInput;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

/**
 * @covers \Source\Account\Invitation\Application\UseCase\Command\InviteMember\InviteMemberInput
 */
class InviteMemberInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成できること
     */
    public function test__construct(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $inviterIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $emails = [
            new Email('test1@example.com'),
            new Email('test2@example.com'),
        ];

        $input = new InviteMemberInput(
            $accountIdentifier,
            $inviterIdentityIdentifier,
            $emails,
        );

        $this->assertSame($accountIdentifier, $input->accountIdentifier());
        $this->assertSame($inviterIdentityIdentifier, $input->inviterIdentityIdentifier());
        $this->assertSame($emails, $input->emails());
    }

    /**
     * 正常系: 空のemails配列でもインスタンスが正しく作成できること
     */
    public function test__constructWithEmptyEmails(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $inviterIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $emails = [];

        $input = new InviteMemberInput(
            $accountIdentifier,
            $inviterIdentityIdentifier,
            $emails,
        );

        $this->assertSame($accountIdentifier, $input->accountIdentifier());
        $this->assertSame($inviterIdentityIdentifier, $input->inviterIdentityIdentifier());
        $this->assertSame([], $input->emails());
    }
}
