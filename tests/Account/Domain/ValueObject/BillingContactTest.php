<?php

declare(strict_types=1);

namespace Tests\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Domain\ValueObject\BillingContact;
use Source\Account\Domain\ValueObject\ContractName;
use Source\Account\Domain\ValueObject\Phone;
use Source\Shared\Domain\ValueObject\Email;

class BillingContactTest extends TestCase
{
    /**
     * 正常系: インスタンスを正しく作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $name = new ContractName('Taro Example');
        $email = new Email('taro@example.com');
        $phone = new Phone('+81-3-0000-0000');
        $billingContact = new BillingContact(
            $name,
            $email,
            $phone
        );
        $this->assertSame($name, $billingContact->name());
        $this->assertSame($email, $billingContact->email());
        $this->assertSame($phone, $billingContact->phone());
    }
}
