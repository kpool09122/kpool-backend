<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Infrastructure\Repository;

use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Account\Domain\Entity\RegisteredPaymentMethod;
use Source\Monetization\Account\Domain\Repository\RegisteredPaymentMethodRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodMeta;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodStatus;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Account\Domain\ValueObject\RegisteredPaymentMethodIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateMonetizationAccount;
use Tests\Helper\CreateRegisteredPaymentMethod;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RegisteredPaymentMethodRepositoryTest extends TestCase
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
     * 正常系: 正しくIDに紐づくRegisteredPaymentMethodを取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $paymentMethodIdentifierId = StrTestHelper::generateUuid();
        $stripePaymentMethodId = 'pm_' . StrTestHelper::generateStr(20);

        CreateRegisteredPaymentMethod::create($paymentMethodIdentifierId, [
            'monetization_account_id' => $this->monetizationAccountId,
            'stripe_payment_method_id' => $stripePaymentMethodId,
            'type' => 'card',
            'brand' => 'visa',
            'last4' => '4242',
            'exp_month' => 12,
            'exp_year' => 2030,
            'is_default' => true,
            'status' => 'active',
        ]);

        $repository = $this->app->make(RegisteredPaymentMethodRepositoryInterface::class);
        $result = $repository->findById(new RegisteredPaymentMethodIdentifier($paymentMethodIdentifierId));

        $this->assertNotNull($result);
        $this->assertSame($paymentMethodIdentifierId, (string) $result->paymentMethodIdentifier());
        $this->assertSame($this->monetizationAccountId, (string) $result->monetizationAccountIdentifier());
        $this->assertSame($stripePaymentMethodId, (string) $result->paymentMethodId());
        $this->assertSame(PaymentMethodType::CARD, $result->type());
        $this->assertNotNull($result->meta());
        $this->assertSame('visa', $result->meta()->brand());
        $this->assertSame('4242', $result->meta()->last4());
        $this->assertSame(12, $result->meta()->expMonth());
        $this->assertSame(2030, $result->meta()->expYear());
        $this->assertTrue($result->isDefault());
        $this->assertSame(PaymentMethodStatus::ACTIVE, $result->status());
    }

    /**
     * 正常系: メタ情報がnullのRegisteredPaymentMethodを取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithoutMeta(): void
    {
        $paymentMethodIdentifierId = StrTestHelper::generateUuid();

        CreateRegisteredPaymentMethod::create($paymentMethodIdentifierId, [
            'monetization_account_id' => $this->monetizationAccountId,
        ]);

        $repository = $this->app->make(RegisteredPaymentMethodRepositoryInterface::class);
        $result = $repository->findById(new RegisteredPaymentMethodIdentifier($paymentMethodIdentifierId));

        $this->assertNotNull($result);
        $this->assertNull($result->meta());
        $this->assertFalse($result->isDefault());
        $this->assertSame(PaymentMethodStatus::ACTIVE, $result->status());
    }

    /**
     * 正常系: 指定したIDを持つRegisteredPaymentMethodが存在しない場合、NULLが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotFound(): void
    {
        $repository = $this->app->make(RegisteredPaymentMethodRepositoryInterface::class);
        $result = $repository->findById(new RegisteredPaymentMethodIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: PaymentMethodIdに紐づくRegisteredPaymentMethodを取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByPaymentMethodId(): void
    {
        $paymentMethodIdentifierId = StrTestHelper::generateUuid();
        $stripePaymentMethodId = 'pm_' . StrTestHelper::generateStr(20);

        CreateRegisteredPaymentMethod::create($paymentMethodIdentifierId, [
            'monetization_account_id' => $this->monetizationAccountId,
            'stripe_payment_method_id' => $stripePaymentMethodId,
        ]);

        $repository = $this->app->make(RegisteredPaymentMethodRepositoryInterface::class);
        $result = $repository->findByPaymentMethodId(new PaymentMethodId($stripePaymentMethodId));

        $this->assertNotNull($result);
        $this->assertSame($paymentMethodIdentifierId, (string) $result->paymentMethodIdentifier());
        $this->assertSame($stripePaymentMethodId, (string) $result->paymentMethodId());
    }

    /**
     * 正常系: 指定したPaymentMethodIdを持つRegisteredPaymentMethodが存在しない場合、NULLが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByPaymentMethodIdWhenNotFound(): void
    {
        $repository = $this->app->make(RegisteredPaymentMethodRepositoryInterface::class);
        $result = $repository->findByPaymentMethodId(new PaymentMethodId('pm_' . StrTestHelper::generateStr(20)));

        $this->assertNull($result);
    }

    /**
     * 正常系: MonetizationAccountIdに紐づくデフォルトのRegisteredPaymentMethodを取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindDefaultByMonetizationAccountId(): void
    {
        $defaultId = StrTestHelper::generateUuid();
        $nonDefaultId = StrTestHelper::generateUuid();

        CreateRegisteredPaymentMethod::create($defaultId, [
            'monetization_account_id' => $this->monetizationAccountId,
            'is_default' => true,
        ]);
        CreateRegisteredPaymentMethod::create($nonDefaultId, [
            'monetization_account_id' => $this->monetizationAccountId,
            'is_default' => false,
        ]);

        $repository = $this->app->make(RegisteredPaymentMethodRepositoryInterface::class);
        $result = $repository->findDefaultByMonetizationAccountId(
            new MonetizationAccountIdentifier($this->monetizationAccountId)
        );

        $this->assertNotNull($result);
        $this->assertSame($defaultId, (string) $result->paymentMethodIdentifier());
        $this->assertTrue($result->isDefault());
    }

    /**
     * 正常系: デフォルトのRegisteredPaymentMethodが存在しない場合、NULLが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindDefaultByMonetizationAccountIdWhenNotFound(): void
    {
        $repository = $this->app->make(RegisteredPaymentMethodRepositoryInterface::class);
        $result = $repository->findDefaultByMonetizationAccountId(
            new MonetizationAccountIdentifier(StrTestHelper::generateUuid())
        );

        $this->assertNull($result);
    }

    /**
     * 正常系: MonetizationAccountIdに紐づく全てのRegisteredPaymentMethodを取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByMonetizationAccountId(): void
    {
        $paymentMethodId1 = StrTestHelper::generateUuid();
        $paymentMethodId2 = StrTestHelper::generateUuid();

        CreateRegisteredPaymentMethod::create($paymentMethodId1, [
            'monetization_account_id' => $this->monetizationAccountId,
        ]);
        CreateRegisteredPaymentMethod::create($paymentMethodId2, [
            'monetization_account_id' => $this->monetizationAccountId,
        ]);

        $repository = $this->app->make(RegisteredPaymentMethodRepositoryInterface::class);
        $results = $repository->findByMonetizationAccountId(
            new MonetizationAccountIdentifier($this->monetizationAccountId)
        );

        $this->assertCount(2, $results);

        $resultIds = array_map(
            static fn (RegisteredPaymentMethod $method) => (string) $method->paymentMethodIdentifier(),
            $results
        );
        $this->assertContains($paymentMethodId1, $resultIds);
        $this->assertContains($paymentMethodId2, $resultIds);
    }

    /**
     * 正常系: MonetizationAccountIdに紐づくRegisteredPaymentMethodが存在しない場合、空配列が返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByMonetizationAccountIdWhenEmpty(): void
    {
        $repository = $this->app->make(RegisteredPaymentMethodRepositoryInterface::class);
        $results = $repository->findByMonetizationAccountId(
            new MonetizationAccountIdentifier(StrTestHelper::generateUuid())
        );

        $this->assertCount(0, $results);
    }

    // -------------------------------------------------------------------------
    // save 系テスト
    // -------------------------------------------------------------------------

    /**
     * 正常系: 正しく新規のRegisteredPaymentMethodを保存できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithNewPaymentMethod(): void
    {
        $paymentMethodIdentifierId = StrTestHelper::generateUuid();
        $stripePaymentMethodId = 'pm_' . StrTestHelper::generateStr(20);

        $paymentMethod = new RegisteredPaymentMethod(
            new RegisteredPaymentMethodIdentifier($paymentMethodIdentifierId),
            new MonetizationAccountIdentifier($this->monetizationAccountId),
            new PaymentMethodId($stripePaymentMethodId),
            PaymentMethodType::CARD,
        );

        $repository = $this->app->make(RegisteredPaymentMethodRepositoryInterface::class);
        $repository->save($paymentMethod);

        $this->assertDatabaseHas('monetization_payment_methods', [
            'id' => $paymentMethodIdentifierId,
            'monetization_account_id' => $this->monetizationAccountId,
            'stripe_payment_method_id' => $stripePaymentMethodId,
            'type' => 'card',
            'brand' => null,
            'last4' => null,
            'exp_month' => null,
            'exp_year' => null,
            'is_default' => false,
            'status' => 'active',
        ]);
    }

    /**
     * 正常系: メタ情報を含むRegisteredPaymentMethodを保存できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithMeta(): void
    {
        $paymentMethodIdentifierId = StrTestHelper::generateUuid();
        $stripePaymentMethodId = 'pm_' . StrTestHelper::generateStr(20);

        $meta = new PaymentMethodMeta(
            'mastercard',
            '5555',
            6,
            2028,
        );

        $paymentMethod = new RegisteredPaymentMethod(
            new RegisteredPaymentMethodIdentifier($paymentMethodIdentifierId),
            new MonetizationAccountIdentifier($this->monetizationAccountId),
            new PaymentMethodId($stripePaymentMethodId),
            PaymentMethodType::CARD,
            $meta,
            true,
        );

        $repository = $this->app->make(RegisteredPaymentMethodRepositoryInterface::class);
        $repository->save($paymentMethod);

        $this->assertDatabaseHas('monetization_payment_methods', [
            'id' => $paymentMethodIdentifierId,
            'stripe_payment_method_id' => $stripePaymentMethodId,
            'type' => 'card',
            'brand' => 'mastercard',
            'last4' => '5555',
            'exp_month' => 6,
            'exp_year' => 2028,
            'is_default' => true,
            'status' => 'active',
        ]);
    }

    /**
     * 正常系: 既存のRegisteredPaymentMethodを更新できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithExistingPaymentMethod(): void
    {
        $paymentMethodIdentifierId = StrTestHelper::generateUuid();
        $stripePaymentMethodId = 'pm_' . StrTestHelper::generateStr(20);

        $paymentMethod = new RegisteredPaymentMethod(
            new RegisteredPaymentMethodIdentifier($paymentMethodIdentifierId),
            new MonetizationAccountIdentifier($this->monetizationAccountId),
            new PaymentMethodId($stripePaymentMethodId),
            PaymentMethodType::CARD,
        );

        $repository = $this->app->make(RegisteredPaymentMethodRepositoryInterface::class);
        $repository->save($paymentMethod);

        // メタ情報を追加して更新
        $paymentMethod->updateMeta(new PaymentMethodMeta(
            'visa',
            '4242',
            3,
            2029,
        ));
        $paymentMethod->markAsDefault();
        $repository->save($paymentMethod);

        $this->assertDatabaseHas('monetization_payment_methods', [
            'id' => $paymentMethodIdentifierId,
            'brand' => 'visa',
            'last4' => '4242',
            'exp_month' => 3,
            'exp_year' => 2029,
            'is_default' => true,
        ]);

        // 再取得して検証
        $result = $repository->findById(new RegisteredPaymentMethodIdentifier($paymentMethodIdentifierId));
        $this->assertNotNull($result);
        $this->assertSame('visa', $result->meta()->brand());
        $this->assertTrue($result->isDefault());
    }

    /**
     * 正常系: RegisteredPaymentMethodのステータスを変更して保存できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithStatusChange(): void
    {
        $paymentMethodIdentifierId = StrTestHelper::generateUuid();
        $stripePaymentMethodId = 'pm_' . StrTestHelper::generateStr(20);

        $paymentMethod = new RegisteredPaymentMethod(
            new RegisteredPaymentMethodIdentifier($paymentMethodIdentifierId),
            new MonetizationAccountIdentifier($this->monetizationAccountId),
            new PaymentMethodId($stripePaymentMethodId),
            PaymentMethodType::CARD,
        );

        $repository = $this->app->make(RegisteredPaymentMethodRepositoryInterface::class);
        $repository->save($paymentMethod);

        // ステータスを無効化
        $paymentMethod->deactivate();
        $repository->save($paymentMethod);

        $this->assertDatabaseHas('monetization_payment_methods', [
            'id' => $paymentMethodIdentifierId,
            'status' => 'inactive',
        ]);

        // 再取得して検証
        $result = $repository->findById(new RegisteredPaymentMethodIdentifier($paymentMethodIdentifierId));
        $this->assertSame(PaymentMethodStatus::INACTIVE, $result->status());
    }
}
