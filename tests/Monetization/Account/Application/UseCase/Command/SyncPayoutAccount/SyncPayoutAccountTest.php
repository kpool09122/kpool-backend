<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\UseCase\Command\SyncPayoutAccount;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Monetization\Account\Application\UseCase\Command\SyncPayoutAccount\SyncPayoutAccountInput;
use Source\Monetization\Account\Application\UseCase\Command\SyncPayoutAccount\SyncPayoutAccountInterface;
use Source\Monetization\Account\Domain\Entity\MonetizationAccount;
use Source\Monetization\Account\Domain\Entity\PayoutAccount;
use Source\Monetization\Account\Domain\Exception\MonetizationAccountNotFoundException;
use Source\Monetization\Account\Domain\Factory\PayoutAccountFactoryInterface;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;
use Source\Monetization\Account\Domain\Repository\PayoutAccountRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\ConnectedAccountId;
use Source\Monetization\Account\Domain\ValueObject\ExternalAccountId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PayoutAccountIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SyncPayoutAccountTest extends TestCase
{
    private const string EVENT_UPDATED = 'account.external_account.updated';
    private const string EVENT_DELETED = 'account.external_account.deleted';

    /**
     * 異常系: MonetizationAccountが見つからない場合は例外をスローすること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessThrowsWhenMonetizationAccountNotFound(): void
    {
        $connectedAccountId = 'acct_notfound';
        $externalAccountId = 'ba_test001';

        $input = new SyncPayoutAccountInput(new ConnectedAccountId($connectedAccountId), new ExternalAccountId($externalAccountId), self::EVENT_UPDATED);

        $monetizationAccountRepository = Mockery::mock(MonetizationAccountRepositoryInterface::class);
        $monetizationAccountRepository->shouldReceive('findByConnectedAccountId')
            ->once()
            ->withArgs(fn (ConnectedAccountId $id) => (string) $id === $connectedAccountId)
            ->andReturnNull();

        $payoutAccountRepository = Mockery::mock(PayoutAccountRepositoryInterface::class);
        $payoutAccountRepository->shouldNotReceive('findByExternalAccountId');
        $payoutAccountRepository->shouldNotReceive('save');

        $factory = Mockery::mock(PayoutAccountFactoryInterface::class);
        $factory->shouldNotReceive('create');

        $this->app->instance(MonetizationAccountRepositoryInterface::class, $monetizationAccountRepository);
        $this->app->instance(PayoutAccountRepositoryInterface::class, $payoutAccountRepository);
        $this->app->instance(PayoutAccountFactoryInterface::class, $factory);

        $this->expectException(MonetizationAccountNotFoundException::class);

        $useCase = $this->app->make(SyncPayoutAccountInterface::class);
        $useCase->process($input);
    }

    /**
     * 正常系: 削除イベントかつ既存レコードあり → deactivate() が呼ばれること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessDeactivatesWhenDeletedEventAndRecordExists(): void
    {
        $connectedAccountId = 'acct_test001';
        $externalAccountId = 'ba_delete001';

        $input = new SyncPayoutAccountInput(new ConnectedAccountId($connectedAccountId), new ExternalAccountId($externalAccountId), self::EVENT_DELETED);

        $monetizationAccount = $this->createMonetizationAccount($connectedAccountId);
        $existingPayoutAccount = $this->createPayoutAccount(
            $monetizationAccount->monetizationAccountIdentifier(),
            $externalAccountId,
        );

        $monetizationAccountRepository = Mockery::mock(MonetizationAccountRepositoryInterface::class);
        $monetizationAccountRepository->shouldReceive('findByConnectedAccountId')
            ->once()
            ->andReturn($monetizationAccount);

        $payoutAccountRepository = Mockery::mock(PayoutAccountRepositoryInterface::class);
        $payoutAccountRepository->shouldReceive('findByExternalAccountId')
            ->once()
            ->andReturn($existingPayoutAccount);
        $payoutAccountRepository->shouldReceive('save')
            ->once()
            ->withArgs(fn (PayoutAccount $pa) => $pa->status()->value === 'inactive');
        $payoutAccountRepository->shouldNotReceive('findDefaultByMonetizationAccountId');

        $factory = Mockery::mock(PayoutAccountFactoryInterface::class);
        $factory->shouldNotReceive('create');

        $this->app->instance(MonetizationAccountRepositoryInterface::class, $monetizationAccountRepository);
        $this->app->instance(PayoutAccountRepositoryInterface::class, $payoutAccountRepository);
        $this->app->instance(PayoutAccountFactoryInterface::class, $factory);

        $useCase = $this->app->make(SyncPayoutAccountInterface::class);
        $useCase->process($input);
    }

    /**
     * 正常系: 削除イベントかつ既存レコードなし → 何もしないこと.
     *
     * @throws BindingResolutionException
     */
    public function testProcessDoesNothingWhenDeletedEventAndNoRecord(): void
    {
        $connectedAccountId = 'acct_test002';
        $externalAccountId = 'ba_notfound';

        $input = new SyncPayoutAccountInput(new ConnectedAccountId($connectedAccountId), new ExternalAccountId($externalAccountId), self::EVENT_DELETED);

        $monetizationAccount = $this->createMonetizationAccount($connectedAccountId);

        $monetizationAccountRepository = Mockery::mock(MonetizationAccountRepositoryInterface::class);
        $monetizationAccountRepository->shouldReceive('findByConnectedAccountId')
            ->once()
            ->andReturn($monetizationAccount);

        $payoutAccountRepository = Mockery::mock(PayoutAccountRepositoryInterface::class);
        $payoutAccountRepository->shouldReceive('findByExternalAccountId')
            ->once()
            ->andReturnNull();
        $payoutAccountRepository->shouldNotReceive('save');

        $factory = Mockery::mock(PayoutAccountFactoryInterface::class);
        $factory->shouldNotReceive('create');

        $this->app->instance(MonetizationAccountRepositoryInterface::class, $monetizationAccountRepository);
        $this->app->instance(PayoutAccountRepositoryInterface::class, $payoutAccountRepository);
        $this->app->instance(PayoutAccountFactoryInterface::class, $factory);

        $useCase = $this->app->make(SyncPayoutAccountInterface::class);
        $useCase->process($input);
    }

    /**
     * 正常系: 作成イベントで既存レコードなし → 新規レコードが保存されること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessCreatesNewPayoutAccountWhenNotExists(): void
    {
        $connectedAccountId = 'acct_test003';
        $externalAccountId = 'ba_new00123';

        $input = new SyncPayoutAccountInput(
            new ConnectedAccountId($connectedAccountId),
            new ExternalAccountId($externalAccountId),
            self::EVENT_UPDATED,
            bankName: 'Test Bank',
            last4: '6789',
            country: 'JP',
            currency: 'jpy',
        );

        $monetizationAccount = $this->createMonetizationAccount($connectedAccountId);
        $newPayoutAccount = $this->createPayoutAccount(
            $monetizationAccount->monetizationAccountIdentifier(),
            $externalAccountId,
        );

        $monetizationAccountRepository = Mockery::mock(MonetizationAccountRepositoryInterface::class);
        $monetizationAccountRepository->shouldReceive('findByConnectedAccountId')
            ->once()
            ->andReturn($monetizationAccount);

        $payoutAccountRepository = Mockery::mock(PayoutAccountRepositoryInterface::class);
        $payoutAccountRepository->shouldReceive('findByExternalAccountId')
            ->once()
            ->andReturnNull();
        $payoutAccountRepository->shouldReceive('findDefaultByMonetizationAccountId')
            ->once()
            ->andReturnNull();
        $payoutAccountRepository->shouldReceive('save')->once();

        $factory = Mockery::mock(PayoutAccountFactoryInterface::class);
        $factory->shouldReceive('create')
            ->once()
            ->andReturn($newPayoutAccount);

        $this->app->instance(MonetizationAccountRepositoryInterface::class, $monetizationAccountRepository);
        $this->app->instance(PayoutAccountRepositoryInterface::class, $payoutAccountRepository);
        $this->app->instance(PayoutAccountFactoryInterface::class, $factory);

        $useCase = $this->app->make(SyncPayoutAccountInterface::class);
        $useCase->process($input);
    }

    /**
     * 正常系: 作成イベントかつデフォルトなし → markAsDefault() が呼ばれること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessMarksAsDefaultWhenNoDefaultExists(): void
    {
        $connectedAccountId = 'acct_test004';
        $externalAccountId = 'ba_default001';

        $input = new SyncPayoutAccountInput(
            new ConnectedAccountId($connectedAccountId),
            new ExternalAccountId($externalAccountId),
            self::EVENT_UPDATED,
            bankName: 'Default Bank',
            last4: '0000',
            country: 'JP',
            currency: 'jpy',
        );

        $monetizationAccount = $this->createMonetizationAccount($connectedAccountId);
        $newPayoutAccount = $this->createPayoutAccount(
            $monetizationAccount->monetizationAccountIdentifier(),
            $externalAccountId,
        );

        $monetizationAccountRepository = Mockery::mock(MonetizationAccountRepositoryInterface::class);
        $monetizationAccountRepository->shouldReceive('findByConnectedAccountId')
            ->once()
            ->andReturn($monetizationAccount);

        $payoutAccountRepository = Mockery::mock(PayoutAccountRepositoryInterface::class);
        $payoutAccountRepository->shouldReceive('findByExternalAccountId')
            ->once()
            ->andReturnNull();
        $payoutAccountRepository->shouldReceive('findDefaultByMonetizationAccountId')
            ->once()
            ->andReturnNull();
        $payoutAccountRepository->shouldReceive('save')
            ->once()
            ->withArgs(fn (PayoutAccount $pa) => $pa->isDefault() === true);

        $factory = Mockery::mock(PayoutAccountFactoryInterface::class);
        $factory->shouldReceive('create')->once()->andReturn($newPayoutAccount);

        $this->app->instance(MonetizationAccountRepositoryInterface::class, $monetizationAccountRepository);
        $this->app->instance(PayoutAccountRepositoryInterface::class, $payoutAccountRepository);
        $this->app->instance(PayoutAccountFactoryInterface::class, $factory);

        $useCase = $this->app->make(SyncPayoutAccountInterface::class);
        $useCase->process($input);
    }

    /**
     * 正常系: isDefault=true かつ既存デフォルトありの場合 → markAsDefault() が呼ばれること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessMarksAsDefaultWhenInputIsDefault(): void
    {
        $connectedAccountId = 'acct_test005';
        $externalAccountId = 'ba_default002';

        $input = new SyncPayoutAccountInput(
            new ConnectedAccountId($connectedAccountId),
            new ExternalAccountId($externalAccountId),
            self::EVENT_UPDATED,
            bankName: 'Default Bank',
            last4: '1234',
            country: 'JP',
            currency: 'jpy',
            isDefault: true,
        );

        $monetizationAccount = $this->createMonetizationAccount($connectedAccountId);
        $newPayoutAccount = $this->createPayoutAccount(
            $monetizationAccount->monetizationAccountIdentifier(),
            $externalAccountId,
        );
        $existingDefault = $this->createPayoutAccount(
            $monetizationAccount->monetizationAccountIdentifier(),
            'ba_existing_default',
        );
        $existingDefault->markAsDefault();

        $monetizationAccountRepository = Mockery::mock(MonetizationAccountRepositoryInterface::class);
        $monetizationAccountRepository->shouldReceive('findByConnectedAccountId')
            ->once()
            ->andReturn($monetizationAccount);

        $payoutAccountRepository = Mockery::mock(PayoutAccountRepositoryInterface::class);
        $payoutAccountRepository->shouldReceive('findByExternalAccountId')
            ->once()
            ->andReturnNull();
        $payoutAccountRepository->shouldReceive('findDefaultByMonetizationAccountId')
            ->once()
            ->andReturn($existingDefault);
        $payoutAccountRepository->shouldReceive('save')
            ->once()
            ->withArgs(fn (PayoutAccount $pa) => (string) $pa->externalAccountId() === $externalAccountId
                && $pa->isDefault() === true);

        $factory = Mockery::mock(PayoutAccountFactoryInterface::class);
        $factory->shouldReceive('create')->once()->andReturn($newPayoutAccount);

        $this->app->instance(MonetizationAccountRepositoryInterface::class, $monetizationAccountRepository);
        $this->app->instance(PayoutAccountRepositoryInterface::class, $payoutAccountRepository);
        $this->app->instance(PayoutAccountFactoryInterface::class, $factory);

        $useCase = $this->app->make(SyncPayoutAccountInterface::class);
        $useCase->process($input);
    }

    private function createMonetizationAccount(string $connectedAccountId): MonetizationAccount
    {
        return new MonetizationAccount(
            new MonetizationAccountIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            [],
            null,
            new ConnectedAccountId($connectedAccountId),
        );
    }

    private function createPayoutAccount(
        MonetizationAccountIdentifier $monetizationAccountId,
        string $externalAccountId,
    ): PayoutAccount {
        return new PayoutAccount(
            new PayoutAccountIdentifier(StrTestHelper::generateUuid()),
            $monetizationAccountId,
            new ExternalAccountId($externalAccountId),
        );
    }
}
