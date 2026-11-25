<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Infrastructure\Repository;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Account\Domain\Entity\PaymentMethod;
use Source\Monetization\Account\Domain\Repository\PaymentMethodRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodMeta;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodStatus;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateMonetizationAccount;
use Tests\Helper\CreatePaymentMethod;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PaymentMethodRepositoryTest extends TestCase
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

    #[Group('useDb')]
    public function testRejectsMissingExternalPaymentMethodId(): void
    {
        $id = StrTestHelper::generateUuid();
        CreatePaymentMethod::create($id, ['monetization_account_id' => $this->monetizationAccountId]);

        $this->expectException(QueryException::class);
        DB::table('monetization_payment_methods')->where('id', $id)->update(['external_payment_method_id' => null]);
    }

    #[Group('useDb')]
    public function testScopesExternalPaymentMethodIdsToEachAccount(): void
    {
        $otherAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($otherAccountId);
        $externalId = new ExternalPaymentMethodId('pm_shared123');
        $firstId = StrTestHelper::generateUuid();
        $secondId = StrTestHelper::generateUuid();
        CreatePaymentMethod::create($firstId, ['monetization_account_id' => $this->monetizationAccountId, 'external_payment_method_id' => (string) $externalId]);
        $repository = $this->app()->make(PaymentMethodRepositoryInterface::class);
        CreatePaymentMethod::create($secondId, ['monetization_account_id' => $otherAccountId, 'external_payment_method_id' => (string) $externalId]);
        $first = $repository->findByExternalPaymentMethodId($externalId, new MonetizationAccountIdentifier($this->monetizationAccountId));
        $second = $repository->findByExternalPaymentMethodId($externalId, new MonetizationAccountIdentifier($otherAccountId));
        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame($firstId, (string) $first->paymentMethodIdentifier());
        $this->assertSame($secondId, (string) $second->paymentMethodIdentifier());
    }

    #[Group('useDb')]
    public function testDoesNotFindExternalPaymentMethodFromAnotherAccount(): void
    {
        CreatePaymentMethod::create(StrTestHelper::generateUuid(), [
            'monetization_account_id' => $this->monetizationAccountId,
            'external_payment_method_id' => 'pm_other_account123',
        ]);
        $otherAccountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($otherAccountId);
        $repository = $this->app()->make(PaymentMethodRepositoryInterface::class);

        $this->assertNull($repository->findByExternalPaymentMethodId(
            new ExternalPaymentMethodId('pm_other_account123'),
            new MonetizationAccountIdentifier($otherAccountId),
        ));
    }

    #[Group('useDb')]
    public function testRejectsDuplicateExternalPaymentMethodIdWithinAnAccount(): void
    {
        $attributes = ['monetization_account_id' => $this->monetizationAccountId, 'external_payment_method_id' => 'pm_duplicate123'];
        CreatePaymentMethod::create(StrTestHelper::generateUuid(), $attributes);

        $this->expectException(QueryException::class);
        CreatePaymentMethod::create(StrTestHelper::generateUuid(), $attributes);
    }

    // -------------------------------------------------------------------------
    // find 系テスト
    // -------------------------------------------------------------------------

    #[Group('useDb')]
    public function testPersistsEntityAttributesAndExternalReference(): void
    {
        $accountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($accountId);
        $method = new PaymentMethod(
            new PaymentMethodIdentifier(StrTestHelper::generateUuid()),
            new MonetizationAccountIdentifier($accountId),
            new ExternalPaymentMethodId('pm_1234567890'),
            PaymentMethodType::CARD,
            meta: new PaymentMethodMeta('visa', '4242', 12, 2030),
        );
        $repository = $this->app()->make(PaymentMethodRepositoryInterface::class);
        $repository->save($method);
        $restored = $repository->findById($method->paymentMethodIdentifier());
        $this->assertNotNull($restored);
        $this->assertNotNull($restored->meta());
        $this->assertSame('visa', $restored->meta()->brand());
        $this->assertSame('4242', $restored->meta()->last4());
        $this->assertSame('pm_1234567890', (string) $restored->externalPaymentMethodId());
        $this->assertSame(PaymentMethodType::CARD, $restored->type());
    }

    /**
     * 正常系: 正しくIDに紐づくPaymentMethodを取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $paymentMethodIdentifierId = StrTestHelper::generateUuid();
        $stripePaymentMethodId = 'pm_' . StrTestHelper::generateStr(20);

        CreatePaymentMethod::create($paymentMethodIdentifierId, [
            'monetization_account_id' => $this->monetizationAccountId,
            'external_payment_method_id' => $stripePaymentMethodId,
            'type' => 'card',
            'brand' => 'visa',
            'last4' => '4242',
            'exp_month' => 12,
            'exp_year' => 2030,
            'is_default' => true,
            'status' => 'active',
        ]);

        $repository = $this->app()->make(PaymentMethodRepositoryInterface::class);
        $result = $repository->findById(new PaymentMethodIdentifier($paymentMethodIdentifierId));

        $this->assertNotNull($result);
        $this->assertSame($paymentMethodIdentifierId, (string) $result->paymentMethodIdentifier());
        $this->assertSame($this->monetizationAccountId, (string) $result->monetizationAccountIdentifier());
        $this->assertSame($stripePaymentMethodId, (string) $result->externalPaymentMethodId());
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
     * 正常系: メタ情報がnullのPaymentMethodを取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithoutMeta(): void
    {
        $paymentMethodIdentifierId = StrTestHelper::generateUuid();

        CreatePaymentMethod::create($paymentMethodIdentifierId, [
            'monetization_account_id' => $this->monetizationAccountId,
        ]);

        $repository = $this->app()->make(PaymentMethodRepositoryInterface::class);
        $result = $repository->findById(new PaymentMethodIdentifier($paymentMethodIdentifierId));

        $this->assertNotNull($result);
        $this->assertNull($result->meta());
        $this->assertFalse($result->isDefault());
        $this->assertSame(PaymentMethodStatus::ACTIVE, $result->status());
    }

    /**
     * 正常系: 指定したIDを持つPaymentMethodが存在しない場合、NULLが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotFound(): void
    {
        $repository = $this->app()->make(PaymentMethodRepositoryInterface::class);
        $result = $repository->findById(new PaymentMethodIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: PaymentMethodIdに紐づくPaymentMethodを取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByExternalPaymentMethodId(): void
    {
        $paymentMethodIdentifierId = StrTestHelper::generateUuid();
        $stripePaymentMethodId = 'pm_' . StrTestHelper::generateStr(20);

        CreatePaymentMethod::create($paymentMethodIdentifierId, [
            'monetization_account_id' => $this->monetizationAccountId,
            'external_payment_method_id' => $stripePaymentMethodId,
        ]);

        $repository = $this->app()->make(PaymentMethodRepositoryInterface::class);
        $result = $repository->findByExternalPaymentMethodId(new ExternalPaymentMethodId($stripePaymentMethodId), new MonetizationAccountIdentifier($this->monetizationAccountId));

        $this->assertNotNull($result);
        $this->assertSame($paymentMethodIdentifierId, (string) $result->paymentMethodIdentifier());
        $this->assertSame($stripePaymentMethodId, (string) $result->externalPaymentMethodId());
    }

    /**
     * 正常系: 指定したPaymentMethodIdを持つPaymentMethodが存在しない場合、NULLが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByPaymentMethodIdWhenNotFound(): void
    {
        $repository = $this->app()->make(PaymentMethodRepositoryInterface::class);
        $result = $repository->findByExternalPaymentMethodId(new ExternalPaymentMethodId('pm_' . StrTestHelper::generateStr(20)), new MonetizationAccountIdentifier($this->monetizationAccountId));

        $this->assertNull($result);
    }

    /**
     * 正常系: MonetizationAccountIdに紐づくデフォルトのPaymentMethodを取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindDefaultByMonetizationAccountId(): void
    {
        $defaultId = StrTestHelper::generateUuid();
        $nonDefaultId = StrTestHelper::generateUuid();

        CreatePaymentMethod::create($defaultId, [
            'monetization_account_id' => $this->monetizationAccountId,
            'is_default' => true,
        ]);
        CreatePaymentMethod::create($nonDefaultId, [
            'monetization_account_id' => $this->monetizationAccountId,
            'is_default' => false,
        ]);

        $repository = $this->app()->make(PaymentMethodRepositoryInterface::class);
        $result = $repository->findDefaultByMonetizationAccountId(
            new MonetizationAccountIdentifier($this->monetizationAccountId)
        );

        $this->assertNotNull($result);
        $this->assertSame($defaultId, (string) $result->paymentMethodIdentifier());
        $this->assertTrue($result->isDefault());
    }

    /**
     * 正常系: デフォルトのPaymentMethodが存在しない場合、NULLが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindDefaultByMonetizationAccountIdWhenNotFound(): void
    {
        $repository = $this->app()->make(PaymentMethodRepositoryInterface::class);
        $result = $repository->findDefaultByMonetizationAccountId(
            new MonetizationAccountIdentifier(StrTestHelper::generateUuid())
        );

        $this->assertNull($result);
    }

    /**
     * 正常系: MonetizationAccountIdに紐づく全てのPaymentMethodを取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByMonetizationAccountId(): void
    {
        $externalPaymentMethodId1 = StrTestHelper::generateUuid();
        $externalPaymentMethodId2 = StrTestHelper::generateUuid();

        CreatePaymentMethod::create($externalPaymentMethodId1, [
            'monetization_account_id' => $this->monetizationAccountId,
        ]);
        CreatePaymentMethod::create($externalPaymentMethodId2, [
            'monetization_account_id' => $this->monetizationAccountId,
        ]);

        $repository = $this->app()->make(PaymentMethodRepositoryInterface::class);
        $results = $repository->findByMonetizationAccountId(
            new MonetizationAccountIdentifier($this->monetizationAccountId)
        );

        $this->assertCount(2, $results);

        $resultIds = array_map(
            static fn (PaymentMethod $method) => (string) $method->paymentMethodIdentifier(),
            $results
        );
        $this->assertContains($externalPaymentMethodId1, $resultIds);
        $this->assertContains($externalPaymentMethodId2, $resultIds);
    }

    /**
     * 正常系: MonetizationAccountIdに紐づくPaymentMethodが存在しない場合、空配列が返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByMonetizationAccountIdWhenEmpty(): void
    {
        $repository = $this->app()->make(PaymentMethodRepositoryInterface::class);
        $results = $repository->findByMonetizationAccountId(
            new MonetizationAccountIdentifier(StrTestHelper::generateUuid())
        );

        $this->assertCount(0, $results);
    }

    // -------------------------------------------------------------------------
    // save 系テスト
    // -------------------------------------------------------------------------

    /**
     * 正常系: 正しく新規のPaymentMethodを保存できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithNewPaymentMethod(): void
    {
        $paymentMethodIdentifierId = StrTestHelper::generateUuid();
        $stripePaymentMethodId = 'pm_' . StrTestHelper::generateStr(20);

        $paymentMethod = new PaymentMethod(
            new PaymentMethodIdentifier($paymentMethodIdentifierId),
            new MonetizationAccountIdentifier($this->monetizationAccountId),
            new ExternalPaymentMethodId($stripePaymentMethodId),
            PaymentMethodType::CARD,
        );

        $repository = $this->app()->make(PaymentMethodRepositoryInterface::class);
        $repository->save($paymentMethod);

        $this->assertDatabaseHas('monetization_payment_methods', [
            'id' => $paymentMethodIdentifierId,
            'monetization_account_id' => $this->monetizationAccountId,
            'external_payment_method_id' => $stripePaymentMethodId,
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
     * 正常系: メタ情報を含むPaymentMethodを保存できること.
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

        $paymentMethod = new PaymentMethod(
            new PaymentMethodIdentifier($paymentMethodIdentifierId),
            new MonetizationAccountIdentifier($this->monetizationAccountId),
            new ExternalPaymentMethodId($stripePaymentMethodId),
            PaymentMethodType::CARD,
            $meta,
            true,
        );

        $repository = $this->app()->make(PaymentMethodRepositoryInterface::class);
        $repository->save($paymentMethod);

        $this->assertDatabaseHas('monetization_payment_methods', [
            'id' => $paymentMethodIdentifierId,
            'external_payment_method_id' => $stripePaymentMethodId,
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
     * 正常系: 既存のPaymentMethodを更新できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithExistingPaymentMethod(): void
    {
        $paymentMethodIdentifierId = StrTestHelper::generateUuid();
        $stripePaymentMethodId = 'pm_' . StrTestHelper::generateStr(20);

        $paymentMethod = new PaymentMethod(
            new PaymentMethodIdentifier($paymentMethodIdentifierId),
            new MonetizationAccountIdentifier($this->monetizationAccountId),
            new ExternalPaymentMethodId($stripePaymentMethodId),
            PaymentMethodType::CARD,
        );

        $repository = $this->app()->make(PaymentMethodRepositoryInterface::class);
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
        $result = $repository->findById(new PaymentMethodIdentifier($paymentMethodIdentifierId));
        $this->assertNotNull($result);
        $this->assertNotNull($result->meta());
        $this->assertSame('visa', $result->meta()->brand());
        $this->assertTrue($result->isDefault());
    }

    /**
     * 正常系: PaymentMethodのステータスを変更して保存できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithStatusChange(): void
    {
        $paymentMethodIdentifierId = StrTestHelper::generateUuid();
        $stripePaymentMethodId = 'pm_' . StrTestHelper::generateStr(20);

        $paymentMethod = new PaymentMethod(
            new PaymentMethodIdentifier($paymentMethodIdentifierId),
            new MonetizationAccountIdentifier($this->monetizationAccountId),
            new ExternalPaymentMethodId($stripePaymentMethodId),
            PaymentMethodType::CARD,
        );

        $repository = $this->app()->make(PaymentMethodRepositoryInterface::class);
        $repository->save($paymentMethod);

        // ステータスを無効化
        $paymentMethod->deactivate();
        $repository->save($paymentMethod);

        $this->assertDatabaseHas('monetization_payment_methods', [
            'id' => $paymentMethodIdentifierId,
            'status' => 'inactive',
        ]);

        // 再取得して検証
        $result = $repository->findById(new PaymentMethodIdentifier($paymentMethodIdentifierId));
        $this->assertNotNull($result);
        $this->assertSame(PaymentMethodStatus::INACTIVE, $result->status());
    }
}
