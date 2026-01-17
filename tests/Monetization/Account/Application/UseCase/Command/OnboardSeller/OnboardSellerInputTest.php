<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\UseCase\Command\OnboardSeller;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Application\UseCase\Command\OnboardSeller\OnboardSellerInput;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Email;
use Tests\Helper\StrTestHelper;

class OnboardSellerInputTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $monetizationAccountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $email = new Email('email@email.com');
        $code = CountryCode::JAPAN;
        $refreshUrl = 'https://refresh.url';
        $returnUrl = 'https://return.url';
        $input = new OnboardSellerInput(
            $monetizationAccountIdentifier,
            $email,
            $code,
            $refreshUrl,
            $returnUrl,
        );
        $this->assertSame($monetizationAccountIdentifier, $input->monetizationAccountIdentifier());
        $this->assertSame($email, $input->email());
        $this->assertSame($code, $input->countryCode());
        $this->assertSame($refreshUrl, $input->refreshUrl());
        $this->assertSame($returnUrl, $input->returnUrl());
    }
}
