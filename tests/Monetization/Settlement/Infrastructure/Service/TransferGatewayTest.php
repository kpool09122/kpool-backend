<?php

declare(strict_types=1);

namespace Tests\Monetization\Settlement\Infrastructure\Service;

use Application\Http\Client\StripeClient;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Account\Domain\Entity\MonetizationAccount;
use Source\Monetization\Account\Domain\ValueObject\Capability;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\StripeConnectedAccountId;
use Source\Monetization\Settlement\Domain\Entity\Transfer;
use Source\Monetization\Settlement\Domain\Exception\TransferGatewayException;
use Source\Monetization\Settlement\Domain\Service\TransferGatewayInterface;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementBatchIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\TransferIdentifier;
use Source\Monetization\Settlement\Infrastructure\Exception\StripeTransferException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Stripe\Exception\InvalidRequestException;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TransferGatewayTest extends TestCase
{
    /**
     * 正常系: TransferがStripeに正常に実行されること
     *
     * @throws BindingResolutionException
     * @throws TransferGatewayException
     */
    #[Group('useDb')]
    public function testExecuteCreatesStripeTransfer(): void
    {
        $transferId = StrTestHelper::generateUuid();
        $settlementBatchId = StrTestHelper::generateUuid();
        $monetizationAccountId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $stripeConnectedAccountId = 'acct_test123456';

        $transfer = new Transfer(
            new TransferIdentifier($transferId),
            new SettlementBatchIdentifier($settlementBatchId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            new Money(10000, Currency::JPY),
        );

        $account = new MonetizationAccount(
            new MonetizationAccountIdentifier($monetizationAccountId),
            new AccountIdentifier($accountId),
            [Capability::RECEIVE_PAYOUT],
            null,
            new StripeConnectedAccountId($stripeConnectedAccountId),
        );

        // Stripe Transfer のモック
        $mockStripeTransfer = (object) [
            'id' => 'tr_test123456',
        ];

        $mockTransfers = Mockery::mock();
        $mockTransfers->shouldReceive('create')
            ->once()
            ->with([
                'amount' => 10000,
                'currency' => 'jpy',
                'destination' => $stripeConnectedAccountId,
                'metadata' => [
                    'transfer_id' => $transferId,
                    'settlement_batch_id' => $settlementBatchId,
                ],
            ])
            ->andReturn($mockStripeTransfer);

        $mockBaseClient = Mockery::mock(\Stripe\StripeClient::class);
        $mockBaseClient->shouldReceive('getService')
            ->with('transfers')
            ->andReturn($mockTransfers);

        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('client')->andReturn($mockBaseClient);

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(TransferGatewayInterface::class);

        $result = $gateway->execute($transfer, $account);

        $this->assertSame('tr_test123456', (string) $result);
    }

    /**
     * 正常系: USD通貨のTransferが正常に実行されること
     *
     * @throws BindingResolutionException
     * @throws TransferGatewayException
     */
    #[Group('useDb')]
    public function testExecuteCreatesStripeTransferWithUsd(): void
    {
        $transferId = StrTestHelper::generateUuid();
        $settlementBatchId = StrTestHelper::generateUuid();
        $monetizationAccountId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $stripeConnectedAccountId = 'acct_test123456';

        $transfer = new Transfer(
            new TransferIdentifier($transferId),
            new SettlementBatchIdentifier($settlementBatchId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            new Money(1000, Currency::USD),
        );

        $account = new MonetizationAccount(
            new MonetizationAccountIdentifier($monetizationAccountId),
            new AccountIdentifier($accountId),
            [Capability::RECEIVE_PAYOUT],
            null,
            new StripeConnectedAccountId($stripeConnectedAccountId),
        );

        $mockStripeTransfer = (object) [
            'id' => 'tr_test_usd_123456',
        ];

        $mockTransfers = Mockery::mock();
        $mockTransfers->shouldReceive('create')
            ->once()
            ->with([
                'amount' => 1000,
                'currency' => 'usd',
                'destination' => $stripeConnectedAccountId,
                'metadata' => [
                    'transfer_id' => $transferId,
                    'settlement_batch_id' => $settlementBatchId,
                ],
            ])
            ->andReturn($mockStripeTransfer);

        $mockBaseClient = Mockery::mock(\Stripe\StripeClient::class);
        $mockBaseClient->shouldReceive('getService')
            ->with('transfers')
            ->andReturn($mockTransfers);

        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('client')->andReturn($mockBaseClient);

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(TransferGatewayInterface::class);

        $result = $gateway->execute($transfer, $account);

        $this->assertSame('tr_test_usd_123456', (string) $result);
    }

    /**
     * 正常系: KRW通貨のTransferが正常に実行されること
     *
     * @throws BindingResolutionException
     * @throws TransferGatewayException
     */
    #[Group('useDb')]
    public function testExecuteCreatesStripeTransferWithKrw(): void
    {
        $transferId = StrTestHelper::generateUuid();
        $settlementBatchId = StrTestHelper::generateUuid();
        $monetizationAccountId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $stripeConnectedAccountId = 'acct_test123456';

        $transfer = new Transfer(
            new TransferIdentifier($transferId),
            new SettlementBatchIdentifier($settlementBatchId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            new Money(50000, Currency::KRW),
        );

        $account = new MonetizationAccount(
            new MonetizationAccountIdentifier($monetizationAccountId),
            new AccountIdentifier($accountId),
            [Capability::RECEIVE_PAYOUT],
            null,
            new StripeConnectedAccountId($stripeConnectedAccountId),
        );

        $mockStripeTransfer = (object) [
            'id' => 'tr_test_krw_123456',
        ];

        $mockTransfers = Mockery::mock();
        $mockTransfers->shouldReceive('create')
            ->once()
            ->with([
                'amount' => 50000,
                'currency' => 'krw',
                'destination' => $stripeConnectedAccountId,
                'metadata' => [
                    'transfer_id' => $transferId,
                    'settlement_batch_id' => $settlementBatchId,
                ],
            ])
            ->andReturn($mockStripeTransfer);

        $mockBaseClient = Mockery::mock(\Stripe\StripeClient::class);
        $mockBaseClient->shouldReceive('getService')
            ->with('transfers')
            ->andReturn($mockTransfers);

        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('client')->andReturn($mockBaseClient);

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(TransferGatewayInterface::class);

        $result = $gateway->execute($transfer, $account);

        $this->assertSame('tr_test_krw_123456', (string) $result);
    }

    /**
     * 異常系: Stripe APIエラー時にStripeTransferExceptionがスローされること
     *
     * @throws BindingResolutionException
     * @throws TransferGatewayException
     */
    #[Group('useDb')]
    public function testExecuteThrowsStripeTransferExceptionOnApiError(): void
    {
        $transferId = StrTestHelper::generateUuid();
        $settlementBatchId = StrTestHelper::generateUuid();
        $monetizationAccountId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $stripeConnectedAccountId = 'acct_test123456';

        $transfer = new Transfer(
            new TransferIdentifier($transferId),
            new SettlementBatchIdentifier($settlementBatchId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            new Money(10000, Currency::JPY),
        );

        $account = new MonetizationAccount(
            new MonetizationAccountIdentifier($monetizationAccountId),
            new AccountIdentifier($accountId),
            [Capability::RECEIVE_PAYOUT],
            null,
            new StripeConnectedAccountId($stripeConnectedAccountId),
        );

        $mockTransfers = Mockery::mock();
        $mockTransfers->shouldReceive('create')
            ->once()
            ->andThrow(InvalidRequestException::factory(
                'No such connected account',
                400,
                null,
                null,
                null,
                'account_invalid'
            ));

        $mockBaseClient = Mockery::mock(\Stripe\StripeClient::class);
        $mockBaseClient->shouldReceive('getService')
            ->with('transfers')
            ->andReturn($mockTransfers);

        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('client')->andReturn($mockBaseClient);

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(TransferGatewayInterface::class);

        $this->expectException(StripeTransferException::class);
        $this->expectExceptionMessage('Transfer failed:');

        $gateway->execute($transfer, $account);
    }

    /**
     * 異常系: 残高不足のStripe APIエラー時にStripeTransferExceptionがスローされること
     *
     * @throws BindingResolutionException
     * @throws TransferGatewayException
     */
    #[Group('useDb')]
    public function testExecuteThrowsStripeTransferExceptionOnInsufficientFunds(): void
    {
        $transferId = StrTestHelper::generateUuid();
        $settlementBatchId = StrTestHelper::generateUuid();
        $monetizationAccountId = StrTestHelper::generateUuid();
        $accountId = StrTestHelper::generateUuid();
        $stripeConnectedAccountId = 'acct_test123456';

        $transfer = new Transfer(
            new TransferIdentifier($transferId),
            new SettlementBatchIdentifier($settlementBatchId),
            new MonetizationAccountIdentifier($monetizationAccountId),
            new Money(10000, Currency::JPY),
        );

        $account = new MonetizationAccount(
            new MonetizationAccountIdentifier($monetizationAccountId),
            new AccountIdentifier($accountId),
            [Capability::RECEIVE_PAYOUT],
            null,
            new StripeConnectedAccountId($stripeConnectedAccountId),
        );

        $mockTransfers = Mockery::mock();
        $mockTransfers->shouldReceive('create')
            ->once()
            ->andThrow(InvalidRequestException::factory(
                'Insufficient funds in your Stripe balance',
                400,
                null,
                null,
                null,
                'balance_insufficient'
            ));

        $mockBaseClient = Mockery::mock(\Stripe\StripeClient::class);
        $mockBaseClient->shouldReceive('getService')
            ->with('transfers')
            ->andReturn($mockTransfers);

        $mockStripeClient = Mockery::mock(StripeClient::class);
        $mockStripeClient->shouldReceive('client')->andReturn($mockBaseClient);

        $this->app->instance(StripeClient::class, $mockStripeClient);

        $gateway = $this->app->make(TransferGatewayInterface::class);

        $this->expectException(StripeTransferException::class);
        $this->expectExceptionMessage('Transfer failed:');

        $gateway->execute($transfer, $account);
    }
}
