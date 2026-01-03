<?php

declare(strict_types=1);

namespace Tests\Account\Application\UseCase\Command\WithdrawFromMembership;

use PHPUnit\Framework\TestCase;
use Source\Account\Application\UseCase\Command\WithdrawFromMembership\WithdrawFromMembershipInput;
use Source\Account\Domain\Entity\AccountMembership;
use Source\Account\Domain\ValueObject\AccountRole;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class WithdrawFromMembershipInputTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成できること.
     *
     * @return void
     */
    public function test_construct(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $accountMembership = new AccountMembership(
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            AccountRole::MEMBER
        );

        $input = new WithdrawFromMembershipInput($accountIdentifier, $accountMembership);

        $this->assertSame($accountIdentifier, $input->accountIdentifier());
        $this->assertSame($accountMembership, $input->accountMembership());
    }
}
