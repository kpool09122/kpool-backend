<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\UseCase\Command\ProvisionMonetizationAccount;

use Source\Monetization\Account\Application\UseCase\Command\ProvisionMonetizationAccount\ProvisionMonetizationAccountOutput;
use Source\Monetization\Account\Domain\Entity\MonetizationAccount;
use Source\Monetization\Account\Domain\ValueObject\Capability;
use Source\Monetization\Account\Domain\ValueObject\ConnectedAccountId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentCustomerId;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ProvisionMonetizationAccountOutputTest extends TestCase
{
    /**
     * 正常系: MonetizationAccountがセットされている場合、toArrayが正しい値を返すこと
     */
    public function testToArrayWithMonetizationAccount(): void
    {
        $monetizationAccountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $stripeCustomerId = new PaymentCustomerId('cus_1234567890abcdef');
        $stripeConnectedAccountId = new ConnectedAccountId('acct_1234567890abcdef');

        $account = new MonetizationAccount(
            $monetizationAccountIdentifier,
            $accountIdentifier,
            [Capability::PURCHASE, Capability::SELL],
            $stripeCustomerId,
            $stripeConnectedAccountId,
        );

        $output = new ProvisionMonetizationAccountOutput();
        $output->setMonetizationAccount($account);

        $result = $output->toArray();

        $this->assertSame((string) $monetizationAccountIdentifier, $result['monetizationAccountIdentifier']);
        $this->assertSame((string) $accountIdentifier, $result['accountIdentifier']);
        $this->assertSame(['purchase', 'sell'], $result['capabilities']);
        $this->assertSame('cus_1234567890abcdef', $result['stripeCustomerId']);
        $this->assertSame('acct_1234567890abcdef', $result['stripeConnectedAccountId']);
    }

    /**
     * 正常系: MonetizationAccountがセットされていない場合、toArrayが全てnullの配列を返すこと
     */
    public function testToArrayWithoutMonetizationAccount(): void
    {
        $output = new ProvisionMonetizationAccountOutput();

        $result = $output->toArray();

        $this->assertSame([
            'monetizationAccountIdentifier' => null,
            'accountIdentifier' => null,
            'capabilities' => null,
            'stripeCustomerId' => null,
            'stripeConnectedAccountId' => null,
        ], $result);
    }

    /**
     * 正常系: Stripeの情報がない新規アカウントの場合、正しく出力されること
     */
    public function testToArrayWithNewAccount(): void
    {
        $monetizationAccountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $account = new MonetizationAccount(
            $monetizationAccountIdentifier,
            $accountIdentifier,
            [],
            null,
            null,
        );

        $output = new ProvisionMonetizationAccountOutput();
        $output->setMonetizationAccount($account);

        $result = $output->toArray();

        $this->assertSame((string) $monetizationAccountIdentifier, $result['monetizationAccountIdentifier']);
        $this->assertSame((string) $accountIdentifier, $result['accountIdentifier']);
        $this->assertSame([], $result['capabilities']);
        $this->assertNull($result['stripeCustomerId']);
        $this->assertNull($result['stripeConnectedAccountId']);
    }
}
