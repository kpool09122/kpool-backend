<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\CreateAccount;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountInput;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class CreateAccountInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成できること（identityIdentifierあり）.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $email = new Email('test@test.com');
        $accountType = AccountType::INDIVIDUAL;
        $accountName = new AccountName('test-account');
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $input = new CreateAccountInput(
            $email,
            $accountType,
            $accountName,
            $identityIdentifier,
        );

        $this->assertSame($email, $input->email());
        $this->assertSame($accountType, $input->accountType());
        $this->assertSame($accountName, $input->accountName());
        $this->assertSame($identityIdentifier, $input->identityIdentifier());
    }

    /**
     * 正常系: identityIdentifierがnullでもインスタンスが正しく作成できること.
     *
     * @return void
     */
    public function test__constructWithoutIdentityIdentifier(): void
    {
        $email = new Email('test@test.com');
        $accountType = AccountType::INDIVIDUAL;
        $accountName = new AccountName('test-account');

        $input = new CreateAccountInput(
            $email,
            $accountType,
            $accountName,
        );

        $this->assertSame($email, $input->email());
        $this->assertSame($accountType, $input->accountType());
        $this->assertSame($accountName, $input->accountName());
        $this->assertNull($input->identityIdentifier());
    }
}
