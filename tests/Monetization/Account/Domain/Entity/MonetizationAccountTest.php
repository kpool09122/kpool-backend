<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\Entity;

use DomainException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\Entity\MonetizationAccount;
use Source\Monetization\Account\Domain\Exception\CapabilityAlreadyGrantedException;
use Source\Monetization\Account\Domain\Exception\CapabilityNotGrantedException;
use Source\Monetization\Account\Domain\ValueObject\AddressLine;
use Source\Monetization\Account\Domain\ValueObject\BillingAddress;
use Source\Monetization\Account\Domain\ValueObject\BillingContact;
use Source\Monetization\Account\Domain\ValueObject\BillingMethod;
use Source\Monetization\Account\Domain\ValueObject\Capability;
use Source\Monetization\Account\Domain\ValueObject\City;
use Source\Monetization\Account\Domain\ValueObject\ContractName;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\Phone;
use Source\Monetization\Account\Domain\ValueObject\PostalCode;
use Source\Monetization\Account\Domain\ValueObject\StateOrProvince;
use Source\Monetization\Account\Domain\ValueObject\StripeConnectedAccountId;
use Source\Monetization\Account\Domain\ValueObject\StripeCustomerId;
use Source\Monetization\Account\Domain\ValueObject\TaxCategory;
use Source\Monetization\Account\Domain\ValueObject\TaxInfo;
use Source\Monetization\Account\Domain\ValueObject\TaxRegion;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Email;
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

    /**
     * 正常系: BillingAddressのsetterが正しく動作すること
     */
    public function testSetBillingAddress(): void
    {
        $account = $this->createAccount([]);

        $this->assertNull($account->billingAddress());

        $billingAddress = new BillingAddress(
            CountryCode::JAPAN,
            new PostalCode('100-0001'),
            new StateOrProvince('東京都'),
            new City('千代田区'),
            new AddressLine('丸の内1-1-1'),
        );
        $account->setBillingAddress($billingAddress);

        $this->assertNotNull($account->billingAddress());
        $this->assertSame(CountryCode::JAPAN, $account->billingAddress()->countryCode());
        $this->assertSame('100-0001', (string) $account->billingAddress()->postalCode());
    }

    /**
     * 正常系: BillingContactのsetterが正しく動作すること
     */
    public function testSetBillingContact(): void
    {
        $account = $this->createAccount([]);

        $this->assertNull($account->billingContact());

        $billingContact = new BillingContact(
            new ContractName('山田 太郎'),
            new Email('billing@example.com'),
            new Phone('+81312345678'),
        );
        $account->setBillingContact($billingContact);

        $this->assertNotNull($account->billingContact());
        $this->assertSame('山田 太郎', (string) $account->billingContact()->name());
        $this->assertSame('billing@example.com', (string) $account->billingContact()->email());
        $this->assertSame('+81312345678', (string) $account->billingContact()->phone());
    }

    /**
     * 正常系: BillingMethodのsetterが正しく動作すること
     */
    public function testSetBillingMethod(): void
    {
        $account = $this->createAccount([]);

        $this->assertNull($account->billingMethod());

        $account->setBillingMethod(BillingMethod::CREDIT_CARD);
        $this->assertSame(BillingMethod::CREDIT_CARD, $account->billingMethod());

        $account->setBillingMethod(BillingMethod::INVOICE);
        $this->assertSame(BillingMethod::INVOICE, $account->billingMethod());
    }

    /**
     * 正常系: TaxInfoのsetterが正しく動作すること
     */
    public function testSetTaxInfo(): void
    {
        $account = $this->createAccount([]);

        $this->assertNull($account->taxInfo());

        $taxInfo = new TaxInfo(
            TaxRegion::JP,
            TaxCategory::TAXABLE,
            'T1234567890123',
        );
        $account->setTaxInfo($taxInfo);

        $this->assertNotNull($account->taxInfo());
        $this->assertSame(TaxRegion::JP, $account->taxInfo()->region());
        $this->assertSame(TaxCategory::TAXABLE, $account->taxInfo()->category());
        $this->assertSame('T1234567890123', $account->taxInfo()->taxCode());
    }

    /**
     * 正常系: setBillingInfoでBilling情報をまとめて設定できること
     */
    public function testSetBillingInfo(): void
    {
        $account = $this->createAccount([]);

        $this->assertNull($account->billingAddress());
        $this->assertNull($account->billingContact());
        $this->assertNull($account->billingMethod());
        $this->assertNull($account->taxInfo());

        $billingAddress = new BillingAddress(
            CountryCode::UNITED_STATES,
            new PostalCode('10001'),
            new StateOrProvince('New York'),
            new City('New York'),
            new AddressLine('123 Main Street'),
        );
        $billingContact = new BillingContact(
            new ContractName('John Doe'),
            new Email('john@example.com'),
        );
        $billingMethod = BillingMethod::BANK_TRANSFER;
        $taxInfo = new TaxInfo(
            TaxRegion::US,
            TaxCategory::EXEMPT,
        );

        $account->setBillingInfo($billingAddress, $billingContact, $billingMethod, $taxInfo);

        $this->assertNotNull($account->billingAddress());
        $this->assertSame(CountryCode::UNITED_STATES, $account->billingAddress()->countryCode());

        $this->assertNotNull($account->billingContact());
        $this->assertSame('John Doe', (string) $account->billingContact()->name());

        $this->assertSame(BillingMethod::BANK_TRANSFER, $account->billingMethod());

        $this->assertNotNull($account->taxInfo());
        $this->assertSame(TaxRegion::US, $account->taxInfo()->region());
    }

    /**
     * 正常系: setBillingInfoでnullを設定してBilling情報をクリアできること
     */
    public function testSetBillingInfoWithNull(): void
    {
        $account = $this->createAccount([]);

        // まず設定
        $account->setBillingInfo(
            new BillingAddress(
                CountryCode::JAPAN,
                new PostalCode('100-0001'),
                new StateOrProvince('東京都'),
                new City('千代田区'),
                new AddressLine('丸の内1-1-1'),
            ),
            new BillingContact(
                new ContractName('山田 太郎'),
                new Email('test@example.com'),
            ),
            BillingMethod::CREDIT_CARD,
            new TaxInfo(TaxRegion::JP, TaxCategory::TAXABLE),
        );

        $this->assertNotNull($account->billingAddress());
        $this->assertNotNull($account->billingContact());
        $this->assertNotNull($account->billingMethod());
        $this->assertNotNull($account->taxInfo());

        // nullで上書き
        $account->setBillingInfo(null, null, null, null);

        $this->assertNull($account->billingAddress());
        $this->assertNull($account->billingContact());
        $this->assertNull($account->billingMethod());
        $this->assertNull($account->taxInfo());
    }
}
