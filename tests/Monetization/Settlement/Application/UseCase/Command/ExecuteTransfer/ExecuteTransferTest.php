<?php

declare(strict_types=1);

namespace Tests\Monetization\Settlement\Application\UseCase\Command\ExecuteTransfer;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Monetization\Account\Domain\Entity\MonetizationAccount;
use Source\Monetization\Account\Domain\Exception\MonetizationAccountNotFoundException;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\Capability;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\StripeConnectedAccountId;
use Source\Monetization\Settlement\Application\UseCase\Command\ExecuteTransfer\ExecuteTransferInput;
use Source\Monetization\Settlement\Application\UseCase\Command\ExecuteTransfer\ExecuteTransferInterface;
use Source\Monetization\Settlement\Domain\Entity\Transfer;
use Source\Monetization\Settlement\Domain\Exception\TransferGatewayException;
use Source\Monetization\Settlement\Domain\Exception\TransferNotFoundException;
use Source\Monetization\Settlement\Domain\Repository\TransferRepositoryInterface;
use Source\Monetization\Settlement\Domain\Service\TransferGatewayInterface;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementBatchIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\StripeTransferId;
use Source\Monetization\Settlement\Domain\ValueObject\TransferIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\TransferStatus;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ExecuteTransferTest extends TestCase
{
    /**
     * 正常系: 送金が正常に実行されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws MonetizationAccountNotFoundException
     * @throws TransferNotFoundException
     */
    public function testExecuteTransferSuccessfully(): void
    {
        $transferIdentifier = new TransferIdentifier(StrTestHelper::generateUuid());
        $monetizationAccountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $stripeTransferId = new StripeTransferId('tr_1234567890abcdefghijklmn');

        $transfer = $this->createTransfer($transferIdentifier, $monetizationAccountIdentifier);
        $monetizationAccount = $this->createMonetizationAccount($monetizationAccountIdentifier);

        $transferRepository = Mockery::mock(TransferRepositoryInterface::class);
        $transferRepository->shouldReceive('findById')
            ->once()
            ->with($transferIdentifier)
            ->andReturn($transfer);
        $transferRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static function (Transfer $savedTransfer) use ($stripeTransferId) {
                return $savedTransfer->status() === TransferStatus::SENT
                    && $savedTransfer->stripeTransferId() === $stripeTransferId
                    && $savedTransfer->sentAt() !== null;
            }))
            ->andReturnNull();

        $monetizationAccountRepository = Mockery::mock(MonetizationAccountRepositoryInterface::class);
        $monetizationAccountRepository->shouldReceive('findById')
            ->once()
            ->with($monetizationAccountIdentifier)
            ->andReturn($monetizationAccount);

        $transferGateway = Mockery::mock(TransferGatewayInterface::class);
        $transferGateway->shouldReceive('execute')
            ->once()
            ->with($transfer, $monetizationAccount)
            ->andReturn($stripeTransferId);

        $this->app->instance(TransferRepositoryInterface::class, $transferRepository);
        $this->app->instance(MonetizationAccountRepositoryInterface::class, $monetizationAccountRepository);
        $this->app->instance(TransferGatewayInterface::class, $transferGateway);

        $useCase = $this->app->make(ExecuteTransferInterface::class);

        $input = new ExecuteTransferInput($transferIdentifier);
        $useCase->process($input);
    }

    /**
     * 異常系: 送金が見つからない場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws MonetizationAccountNotFoundException
     */
    public function testThrowsExceptionWhenTransferNotFound(): void
    {
        $transferIdentifier = new TransferIdentifier(StrTestHelper::generateUuid());

        $transferRepository = Mockery::mock(TransferRepositoryInterface::class);
        $transferRepository->shouldReceive('findById')
            ->once()
            ->with($transferIdentifier)
            ->andReturn(null);

        $monetizationAccountRepository = Mockery::mock(MonetizationAccountRepositoryInterface::class);
        $monetizationAccountRepository->shouldNotReceive('findById');

        $transferGateway = Mockery::mock(TransferGatewayInterface::class);
        $transferGateway->shouldNotReceive('execute');

        $this->app->instance(TransferRepositoryInterface::class, $transferRepository);
        $this->app->instance(MonetizationAccountRepositoryInterface::class, $monetizationAccountRepository);
        $this->app->instance(TransferGatewayInterface::class, $transferGateway);

        $this->expectException(TransferNotFoundException::class);

        $useCase = $this->app->make(ExecuteTransferInterface::class);

        $input = new ExecuteTransferInput($transferIdentifier);
        $useCase->process($input);
    }

    /**
     * 異常系: ゲートウェイエラー時に送金が失敗状態になること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws MonetizationAccountNotFoundException
     * @throws TransferNotFoundException
     */
    public function testMarkTransferAsFailedOnGatewayError(): void
    {
        $transferIdentifier = new TransferIdentifier(StrTestHelper::generateUuid());
        $monetizationAccountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());

        $transfer = $this->createTransfer($transferIdentifier, $monetizationAccountIdentifier);
        $monetizationAccount = $this->createMonetizationAccount($monetizationAccountIdentifier);

        $transferRepository = Mockery::mock(TransferRepositoryInterface::class);
        $transferRepository->shouldReceive('findById')
            ->once()
            ->with($transferIdentifier)
            ->andReturn($transfer);
        $transferRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(static function (Transfer $savedTransfer) {
                return $savedTransfer->status() === TransferStatus::FAILED
                    && $savedTransfer->failureReason() === 'Stripe API error'
                    && $savedTransfer->failedAt() !== null;
            }))
            ->andReturnNull();

        $monetizationAccountRepository = Mockery::mock(MonetizationAccountRepositoryInterface::class);
        $monetizationAccountRepository->shouldReceive('findById')
            ->once()
            ->with($monetizationAccountIdentifier)
            ->andReturn($monetizationAccount);

        $transferGateway = Mockery::mock(TransferGatewayInterface::class);
        $transferGateway->shouldReceive('execute')
            ->once()
            ->with($transfer, $monetizationAccount)
            ->andThrow(new TransferGatewayException('Stripe API error'));

        $this->app->instance(TransferRepositoryInterface::class, $transferRepository);
        $this->app->instance(MonetizationAccountRepositoryInterface::class, $monetizationAccountRepository);
        $this->app->instance(TransferGatewayInterface::class, $transferGateway);

        $useCase = $this->app->make(ExecuteTransferInterface::class);

        $input = new ExecuteTransferInput($transferIdentifier);
        $useCase->process($input);
    }

    private function createTransfer(
        TransferIdentifier $transferIdentifier,
        MonetizationAccountIdentifier $monetizationAccountIdentifier
    ): Transfer {
        return new Transfer(
            $transferIdentifier,
            new SettlementBatchIdentifier(StrTestHelper::generateUuid()),
            $monetizationAccountIdentifier,
            new Money(10000, Currency::JPY),
            TransferStatus::PENDING
        );
    }

    private function createMonetizationAccount(
        MonetizationAccountIdentifier $monetizationAccountIdentifier
    ): MonetizationAccount {
        return new MonetizationAccount(
            $monetizationAccountIdentifier,
            new AccountIdentifier(StrTestHelper::generateUuid()),
            [Capability::RECEIVE_PAYOUT],
            null,
            new StripeConnectedAccountId('acct_1234567890')
        );
    }
}
