<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\Entity;

use DomainException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\Entity\MonetizationAccount;
use Source\Monetization\Account\Domain\Exception\CapabilityAlreadyGrantedException;
use Source\Monetization\Account\Domain\Exception\CapabilityNotGrantedException;
use Source\Monetization\Account\Domain\ValueObject\Capability;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\StripeConnectedAccountId;
use Source\Monetization\Account\Domain\ValueObject\StripeCustomerId;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;

class MonetizationAccountTest extends TestCase
{
    /**
     * @param Capability[] $capabilities
     */
    private function createAccount(
        array $capabilities = [],
        ?StripeCustomerId $stripeCustomerId = null,
        ?StripeConnectedAccountId $stripeConnectedAccountId = null,
    ): MonetizationAccount {
        return new MonetizationAccount(
            new MonetizationAccountIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            $capabilities,
            $stripeCustomerId,
            $stripeConnectedAccountId,
        );
    }

    /**
     * 正常系: インスタンスが生成されること
     */
    public function test__construct(): void
    {
        $monetizationAccountId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();

        $account = new MonetizationAccount(
            new MonetizationAccountIdentifier($monetizationAccountId),
            new AccountIdentifier($accountId),
            [],
            null,
            null,
        );

        $this->assertSame($monetizationAccountId, (string) $account->monetizationAccountIdentifier());
        $this->assertSame($accountId, (string) $account->accountIdentifier());
        $this->assertSame([], $account->capabilities());
        $this->assertNull($account->stripeCustomerId());
        $this->assertNull($account->stripeConnectedAccountId());
    }

    /**
     * 正常系: Capabilityを持っていることを確認できること
     */
    public function testHasCapability(): void
    {
        $account = $this->createAccount([Capability::PURCHASE]);

        $this->assertTrue($account->hasCapability(Capability::PURCHASE));
        $this->assertFalse($account->hasCapability(Capability::SELL));
    }

    /**
     * 正常系: canPurchaseが正しく動作すること
     */
    public function testCanPurchase(): void
    {
        $accountWithCapability = $this->createAccount([Capability::PURCHASE]);
        $accountWithoutCapability = $this->createAccount([]);

        $this->assertTrue($accountWithCapability->canPurchase());
        $this->assertFalse($accountWithoutCapability->canPurchase());
    }

    /**
     * 正常系: canSellが正しく動作すること
     */
    public function testCanSell(): void
    {
        $accountWithCapability = $this->createAccount([Capability::SELL]);
        $accountWithoutCapability = $this->createAccount([]);

        $this->assertTrue($accountWithCapability->canSell());
        $this->assertFalse($accountWithoutCapability->canSell());
    }

    /**
     * 正常系: canReceivePayoutが正しく動作すること
     */
    public function testCanReceivePayout(): void
    {
        $accountWithCapability = $this->createAccount([Capability::RECEIVE_PAYOUT]);
        $accountWithoutCapability = $this->createAccount([]);

        $this->assertTrue($accountWithCapability->canReceivePayout());
        $this->assertFalse($accountWithoutCapability->canReceivePayout());
    }

    /**
     * 正常系: Capabilityを付与できること
     */
    public function testGrantCapability(): void
    {
        $account = $this->createAccount([]);

        $account->grantCapability(Capability::PURCHASE);

        $this->assertTrue($account->hasCapability(Capability::PURCHASE));
    }

    /**
     * 異常系: 既に付与されているCapabilityを付与しようとすると例外が発生すること
     */
    public function testGrantCapabilityAlreadyGranted(): void
    {
        $account = $this->createAccount([Capability::PURCHASE]);

        $this->expectException(CapabilityAlreadyGrantedException::class);
        $account->grantCapability(Capability::PURCHASE);
    }

    /**
     * 正常系: Capabilityを取り消せること
     */
    public function testRevokeCapability(): void
    {
        $account = $this->createAccount([Capability::PURCHASE, Capability::SELL]);

        $account->revokeCapability(Capability::PURCHASE);

        $this->assertFalse($account->hasCapability(Capability::PURCHASE));
        $this->assertTrue($account->hasCapability(Capability::SELL));
    }

    /**
     * 異常系: 付与されていないCapabilityを取り消そうとすると例外が発生すること
     */
    public function testRevokeCapabilityNotGranted(): void
    {
        $account = $this->createAccount([]);

        $this->expectException(CapabilityNotGrantedException::class);
        $account->revokeCapability(Capability::PURCHASE);
    }

    /**
     * 正常系: Stripe Customerをリンクできること
     */
    public function testLinkStripeCustomer(): void
    {
        $account = $this->createAccount([]);
        $stripeCustomerId = new StripeCustomerId('cus_1234567890abcdef');

        $account->linkStripeCustomer($stripeCustomerId);

        $this->assertSame('cus_1234567890abcdef', (string) $account->stripeCustomerId());
    }

    /**
     * 異常系: 既にStripe Customerがリンクされている場合、例外が発生すること
     */
    public function testLinkStripeCustomerAlreadyLinked(): void
    {
        $account = $this->createAccount(
            [],
            new StripeCustomerId('cus_1234567890abcdef'),
        );

        $this->expectException(DomainException::class);
        $account->linkStripeCustomer(new StripeCustomerId('cus_another1234567'));
    }

    /**
     * 正常系: Stripe Connected Accountをリンクできること
     */
    public function testLinkStripeConnectedAccount(): void
    {
        $account = $this->createAccount([]);
        $stripeConnectedAccountId = new StripeConnectedAccountId('acct_1234567890abcdef');

        $account->linkStripeConnectedAccount($stripeConnectedAccountId);

        $this->assertSame('acct_1234567890abcdef', (string) $account->stripeConnectedAccountId());
    }

    /**
     * 異常系: 既にStripe Connected Accountがリンクされている場合、例外が発生すること
     */
    public function testLinkStripeConnectedAccountAlreadyLinked(): void
    {
        $account = $this->createAccount(
            [],
            null,
            new StripeConnectedAccountId('acct_1234567890abcdef'),
        );

        $this->expectException(DomainException::class);
        $account->linkStripeConnectedAccount(new StripeConnectedAccountId('acct_another1234567'));
    }

    /**
     * 正常系: 購入可能な状態であることを検証できること
     */
    public function testAssertCanMakePurchase(): void
    {
        $account = $this->createAccount(
            [Capability::PURCHASE],
            new StripeCustomerId('cus_1234567890abcdef'),
        );

        $account->assertCanMakePurchase();
        $this->addToAssertionCount(1); // 例外が発生しなければ成功
    }

    /**
     * 異常系: PURCHASEのCapabilityがない場合、例外が発生すること
     */
    public function testAssertCanMakePurchaseWithoutCapability(): void
    {
        $account = $this->createAccount(
            [],
            new StripeCustomerId('cus_1234567890abcdef'),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Account does not have purchase capability.');
        $account->assertCanMakePurchase();
    }

    /**
     * 異常系: Stripe Customerがリンクされていない場合、例外が発生すること
     */
    public function testAssertCanMakePurchaseWithoutStripeCustomer(): void
    {
        $account = $this->createAccount([Capability::PURCHASE]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Stripe Customer is not linked.');
        $account->assertCanMakePurchase();
    }

    /**
     * 正常系: 販売可能な状態であることを検証できること
     */
    public function testAssertCanSell(): void
    {
        $account = $this->createAccount(
            [Capability::SELL],
            null,
            new StripeConnectedAccountId('acct_1234567890abcdef'),
        );

        $account->assertCanSell();
        $this->addToAssertionCount(1);
    }

    /**
     * 異常系: SELLのCapabilityがない場合、例外が発生すること
     */
    public function testAssertCanSellWithoutCapability(): void
    {
        $account = $this->createAccount(
            [],
            null,
            new StripeConnectedAccountId('acct_1234567890abcdef'),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Account does not have sell capability.');
        $account->assertCanSell();
    }

    /**
     * 異常系: Stripe Connected Accountがリンクされていない場合、例外が発生すること
     */
    public function testAssertCanSellWithoutStripeConnectedAccount(): void
    {
        $account = $this->createAccount([Capability::SELL]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Stripe Connected Account is not linked.');
        $account->assertCanSell();
    }

    /**
     * 正常系: 出金受取可能な状態であることを検証できること
     */
    public function testAssertCanReceivePayout(): void
    {
        $account = $this->createAccount(
            [Capability::RECEIVE_PAYOUT],
            null,
            new StripeConnectedAccountId('acct_1234567890abcdef'),
        );

        $account->assertCanReceivePayout();
        $this->addToAssertionCount(1);
    }

    /**
     * 異常系: RECEIVE_PAYOUTのCapabilityがない場合、例外が発生すること
     */
    public function testAssertCanReceivePayoutWithoutCapability(): void
    {
        $account = $this->createAccount(
            [],
            null,
            new StripeConnectedAccountId('acct_1234567890abcdef'),
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Account does not have payout capability.');
        $account->assertCanReceivePayout();
    }

    /**
     * 異常系: Stripe Connected Accountがリンクされていない場合、例外が発生すること
     */
    public function testAssertCanReceivePayoutWithoutStripeConnectedAccount(): void
    {
        $account = $this->createAccount([Capability::RECEIVE_PAYOUT]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Stripe Connected Account is not linked.');
        $account->assertCanReceivePayout();
    }
}
