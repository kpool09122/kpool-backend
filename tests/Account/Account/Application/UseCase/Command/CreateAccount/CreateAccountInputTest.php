<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\CreateAccount;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Command\CreateAccount\CreateAccountInput;
use Source\Account\Account\Domain\ValueObject\AccountName;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
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
            addressCountryCode: 'JP',
            addressAdministrativeAreaCode: '13',
            addressPostalCode: '100-0001',
            addressLocality: '千代田区',
            addressLine1: '千代田1-1',
            addressLine2: 'ビル 2F',
        );

        $this->assertSame($email, $input->email());
        $this->assertSame($accountType, $input->accountType());
        $this->assertSame($accountName, $input->accountName());
        $this->assertSame($identityIdentifier, $input->identityIdentifier());
        $this->assertSame(Language::ENGLISH, $input->language());
        $this->assertSame('JP', $input->addressCountryCode());
        $this->assertSame('13', $input->addressAdministrativeAreaCode());
        $this->assertSame('100-0001', $input->addressPostalCode());
        $this->assertSame('千代田区', $input->addressLocality());
        $this->assertSame('千代田1-1', $input->addressLine1());
        $this->assertSame('ビル 2F', $input->addressLine2());
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
        $language = Language::KOREAN;

        $input = new CreateAccountInput(
            $email,
            $accountType,
            $accountName,
            null,
            $language,
        );

        $this->assertSame($email, $input->email());
        $this->assertSame($accountType, $input->accountType());
        $this->assertSame($accountName, $input->accountName());
        $this->assertNull($input->identityIdentifier());
        $this->assertSame($language, $input->language());
        $this->assertNull($input->addressCountryCode());
        $this->assertNull($input->addressAdministrativeAreaCode());
        $this->assertNull($input->addressPostalCode());
        $this->assertNull($input->addressLocality());
        $this->assertNull($input->addressLine1());
        $this->assertNull($input->addressLine2());
    }
}
