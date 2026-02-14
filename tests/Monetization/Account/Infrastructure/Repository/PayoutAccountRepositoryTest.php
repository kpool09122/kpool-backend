<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Infrastructure\Repository;

use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Account\Domain\Entity\PayoutAccount;
use Source\Monetization\Account\Domain\Repository\PayoutAccountRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\AccountHolderType;
use Source\Monetization\Account\Domain\ValueObject\ExternalAccountId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PayoutAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PayoutAccountMeta;
use Source\Monetization\Account\Domain\ValueObject\PayoutAccountStatus;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateMonetizationAccount;
use Tests\Helper\CreatePayoutAccount;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PayoutAccountRepositoryTest extends TestCase
{
    private string $accountId = '';
    private string $monetizationAccountId = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = StrTestHelper::generateUuid();
        $this->monetizationAccountId = StrTestHelper::generateUuid();

        CreateAccount::create($this->accountId);
        CreateMonetizationAccount::create($this->monetizationAccountId, [
            'account_id' => $this->accountId,
        ]);
    }

    // -------------------------------------------------------------------------
    // find 系テスト
    // -------------------------------------------------------------------------

    /**
     * 正常系: 正しくIDに紐づくPayoutAccountを取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $payoutAccountId = StrTestHelper::generateUuid();
        $externalAccountId = 'ba_' . StrTestHelper::generateStr(20);

        CreatePayoutAccount::create($payoutAccountId, [
            'monetization_account_id' => $this->monetizationAccountId,
            'stripe_external_account_id' => $externalAccountId,
            'bank_name' => 'Test Bank',
            'last4' => '1234',
            'country' => 'JP',
            'currency' => 'jpy',
            'account_holder_type' => 'individual',
            'is_default' => true,
            'status' => 'active',
        ]);

        $repository = $this->app->make(PayoutAccountRepositoryInterface::class);
        $result = $repository->findById(new PayoutAccountIdentifier($payoutAccountId));

        $this->assertNotNull($result);
        $this->assertSame($payoutAccountId, (string) $result->payoutAccountIdentifier());
        $this->assertSame($this->monetizationAccountId, (string) $result->monetizationAccountIdentifier());
        $this->assertSame($externalAccountId, (string) $result->externalAccountId());
        $this->assertNotNull($result->meta());
        $this->assertSame('Test Bank', $result->meta()->bankName());
        $this->assertSame('1234', $result->meta()->last4());
        $this->assertSame('JP', $result->meta()->country());
        $this->assertSame('jpy', $result->meta()->currency());
        $this->assertSame(AccountHolderType::INDIVIDUAL, $result->meta()->accountHolderType());
        $this->assertTrue($result->isDefault());
        $this->assertSame(PayoutAccountStatus::ACTIVE, $result->status());
    }

    /**
     * 正常系: メタ情報がnullのPayoutAccountを取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithoutMeta(): void
    {
        $payoutAccountId = StrTestHelper::generateUuid();

        CreatePayoutAccount::create($payoutAccountId, [
            'monetization_account_id' => $this->monetizationAccountId,
        ]);

        $repository = $this->app->make(PayoutAccountRepositoryInterface::class);
        $result = $repository->findById(new PayoutAccountIdentifier($payoutAccountId));

        $this->assertNotNull($result);
        $this->assertNull($result->meta());
        $this->assertFalse($result->isDefault());
        $this->assertSame(PayoutAccountStatus::ACTIVE, $result->status());
    }

    /**
     * 正常系: 指定したIDを持つPayoutAccountが存在しない場合、NULLが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotFound(): void
    {
        $repository = $this->app->make(PayoutAccountRepositoryInterface::class);
        $result = $repository->findById(new PayoutAccountIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: ExternalAccountIdに紐づくPayoutAccountを取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByExternalAccountId(): void
    {
        $payoutAccountId = StrTestHelper::generateUuid();
        $externalAccountId = 'ba_' . StrTestHelper::generateStr(20);

        CreatePayoutAccount::create($payoutAccountId, [
            'monetization_account_id' => $this->monetizationAccountId,
            'stripe_external_account_id' => $externalAccountId,
        ]);

        $repository = $this->app->make(PayoutAccountRepositoryInterface::class);
        $result = $repository->findByExternalAccountId(new ExternalAccountId($externalAccountId));

        $this->assertNotNull($result);
        $this->assertSame($payoutAccountId, (string) $result->payoutAccountIdentifier());
        $this->assertSame($externalAccountId, (string) $result->externalAccountId());
    }

    /**
     * 正常系: 指定したExternalAccountIdを持つPayoutAccountが存在しない場合、NULLが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByExternalAccountIdWhenNotFound(): void
    {
        $repository = $this->app->make(PayoutAccountRepositoryInterface::class);
        $result = $repository->findByExternalAccountId(new ExternalAccountId('ba_' . StrTestHelper::generateStr(20)));

        $this->assertNull($result);
    }

    /**
     * 正常系: MonetizationAccountIdに紐づくデフォルトのPayoutAccountを取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindDefaultByMonetizationAccountId(): void
    {
        $defaultPayoutAccountId = StrTestHelper::generateUuid();
        $nonDefaultPayoutAccountId = StrTestHelper::generateUuid();

        CreatePayoutAccount::create($defaultPayoutAccountId, [
            'monetization_account_id' => $this->monetizationAccountId,
            'is_default' => true,
        ]);
        CreatePayoutAccount::create($nonDefaultPayoutAccountId, [
            'monetization_account_id' => $this->monetizationAccountId,
            'is_default' => false,
        ]);

        $repository = $this->app->make(PayoutAccountRepositoryInterface::class);
        $result = $repository->findDefaultByMonetizationAccountId(
            new MonetizationAccountIdentifier($this->monetizationAccountId)
        );

        $this->assertNotNull($result);
        $this->assertSame($defaultPayoutAccountId, (string) $result->payoutAccountIdentifier());
        $this->assertTrue($result->isDefault());
    }

    /**
     * 正常系: デフォルトのPayoutAccountが存在しない場合、NULLが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindDefaultByMonetizationAccountIdWhenNotFound(): void
    {
        $repository = $this->app->make(PayoutAccountRepositoryInterface::class);
        $result = $repository->findDefaultByMonetizationAccountId(
            new MonetizationAccountIdentifier(StrTestHelper::generateUuid())
        );

        $this->assertNull($result);
    }

    /**
     * 正常系: MonetizationAccountIdに紐づく全てのPayoutAccountを取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByMonetizationAccountId(): void
    {
        $payoutAccountId1 = StrTestHelper::generateUuid();
        $payoutAccountId2 = StrTestHelper::generateUuid();

        CreatePayoutAccount::create($payoutAccountId1, [
            'monetization_account_id' => $this->monetizationAccountId,
        ]);
        CreatePayoutAccount::create($payoutAccountId2, [
            'monetization_account_id' => $this->monetizationAccountId,
        ]);

        $repository = $this->app->make(PayoutAccountRepositoryInterface::class);
        $results = $repository->findByMonetizationAccountId(
            new MonetizationAccountIdentifier($this->monetizationAccountId)
        );

        $this->assertCount(2, $results);

        $resultIds = array_map(
            static fn (PayoutAccount $account) => (string) $account->payoutAccountIdentifier(),
            $results
        );
        $this->assertContains($payoutAccountId1, $resultIds);
        $this->assertContains($payoutAccountId2, $resultIds);
    }

    /**
     * 正常系: MonetizationAccountIdに紐づくPayoutAccountが存在しない場合、空配列が返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByMonetizationAccountIdWhenEmpty(): void
    {
        $repository = $this->app->make(PayoutAccountRepositoryInterface::class);
        $results = $repository->findByMonetizationAccountId(
            new MonetizationAccountIdentifier(StrTestHelper::generateUuid())
        );

        $this->assertCount(0, $results);
    }

    // -------------------------------------------------------------------------
    // save 系テスト
    // -------------------------------------------------------------------------

    /**
     * 正常系: 正しく新規のPayoutAccountを保存できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithNewAccount(): void
    {
        $payoutAccountId = StrTestHelper::generateUuid();
        $externalAccountId = 'ba_' . StrTestHelper::generateStr(20);

        $payoutAccount = new PayoutAccount(
            new PayoutAccountIdentifier($payoutAccountId),
            new MonetizationAccountIdentifier($this->monetizationAccountId),
            new ExternalAccountId($externalAccountId),
        );

        $repository = $this->app->make(PayoutAccountRepositoryInterface::class);
        $repository->save($payoutAccount);

        $this->assertDatabaseHas('monetization_payout_accounts', [
            'id' => $payoutAccountId,
            'monetization_account_id' => $this->monetizationAccountId,
            'stripe_external_account_id' => $externalAccountId,
            'bank_name' => null,
            'last4' => null,
            'is_default' => false,
            'status' => 'active',
        ]);
    }

    /**
     * 正常系: メタ情報を含むPayoutAccountを保存できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithMeta(): void
    {
        $payoutAccountId = StrTestHelper::generateUuid();
        $externalAccountId = 'ba_' . StrTestHelper::generateStr(20);

        $meta = new PayoutAccountMeta(
            'Test Bank',
            '5678',
            'JP',
            'jpy',
            AccountHolderType::COMPANY,
        );

        $payoutAccount = new PayoutAccount(
            new PayoutAccountIdentifier($payoutAccountId),
            new MonetizationAccountIdentifier($this->monetizationAccountId),
            new ExternalAccountId($externalAccountId),
            $meta,
            true,
        );

        $repository = $this->app->make(PayoutAccountRepositoryInterface::class);
        $repository->save($payoutAccount);

        $this->assertDatabaseHas('monetization_payout_accounts', [
            'id' => $payoutAccountId,
            'stripe_external_account_id' => $externalAccountId,
            'bank_name' => 'Test Bank',
            'last4' => '5678',
            'country' => 'JP',
            'currency' => 'jpy',
            'account_holder_type' => 'company',
            'is_default' => true,
            'status' => 'active',
        ]);
    }

    /**
     * 正常系: 既存のPayoutAccountを更新できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithExistingAccount(): void
    {
        $payoutAccountId = StrTestHelper::generateUuid();
        $externalAccountId = 'ba_' . StrTestHelper::generateStr(20);

        $payoutAccount = new PayoutAccount(
            new PayoutAccountIdentifier($payoutAccountId),
            new MonetizationAccountIdentifier($this->monetizationAccountId),
            new ExternalAccountId($externalAccountId),
        );

        $repository = $this->app->make(PayoutAccountRepositoryInterface::class);
        $repository->save($payoutAccount);

        // メタ情報を追加して更新
        $payoutAccount->updateMeta(new PayoutAccountMeta(
            'Updated Bank',
            '9999',
            'US',
            'usd',
            AccountHolderType::INDIVIDUAL,
        ));
        $payoutAccount->markAsDefault();
        $repository->save($payoutAccount);

        $this->assertDatabaseHas('monetization_payout_accounts', [
            'id' => $payoutAccountId,
            'bank_name' => 'Updated Bank',
            'last4' => '9999',
            'country' => 'US',
            'currency' => 'usd',
            'account_holder_type' => 'individual',
            'is_default' => true,
        ]);

        // 再取得して検証
        $result = $repository->findById(new PayoutAccountIdentifier($payoutAccountId));
        $this->assertNotNull($result);
        $this->assertSame('Updated Bank', $result->meta()->bankName());
        $this->assertTrue($result->isDefault());
    }

    /**
     * 正常系: PayoutAccountのステータスを変更して保存できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithStatusChange(): void
    {
        $payoutAccountId = StrTestHelper::generateUuid();
        $externalAccountId = 'ba_' . StrTestHelper::generateStr(20);

        $payoutAccount = new PayoutAccount(
            new PayoutAccountIdentifier($payoutAccountId),
            new MonetizationAccountIdentifier($this->monetizationAccountId),
            new ExternalAccountId($externalAccountId),
        );

        $repository = $this->app->make(PayoutAccountRepositoryInterface::class);
        $repository->save($payoutAccount);

        // ステータスを無効化
        $payoutAccount->deactivate();
        $repository->save($payoutAccount);

        $this->assertDatabaseHas('monetization_payout_accounts', [
            'id' => $payoutAccountId,
            'status' => 'inactive',
        ]);

        // 再取得して検証
        $result = $repository->findById(new PayoutAccountIdentifier($payoutAccountId));
        $this->assertSame(PayoutAccountStatus::INACTIVE, $result->status());
    }
}
