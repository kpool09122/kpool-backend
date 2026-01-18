<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Identity\Domain\ValueObject\SignupSession;

class SignupSessionTest extends TestCase
{
    /**
     * 正常系: AccountType::INDIVIDUALでインスタンスが作成されること.
     *
     * @return void
     */
    public function test__constructWithIndividualAccountType(): void
    {
        $accountType = AccountType::INDIVIDUAL;

        $session = new SignupSession($accountType);

        $this->assertSame($accountType, $session->accountType());
    }

    /**
     * 正常系: AccountType::CORPORATIONでインスタンスが作成されること.
     *
     * @return void
     */
    public function test__constructWithCorporationAccountType(): void
    {
        $accountType = AccountType::CORPORATION;

        $session = new SignupSession($accountType);

        $this->assertSame($accountType, $session->accountType());
    }
}
