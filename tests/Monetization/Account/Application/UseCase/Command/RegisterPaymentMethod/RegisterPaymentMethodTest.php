<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodInput;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodInterface;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodOutput;
use Source\Monetization\Account\Domain\Entity\RegisteredPaymentMethod;
use Source\Monetization\Account\Domain\Factory\RegisteredPaymentMethodFactoryInterface;
use Source\Monetization\Account\Domain\Repository\RegisteredPaymentMethodRepositoryInterface;
use Source\Monetization\Account\Domain\Service\PaymentMethodMetaResolverInterface;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodMeta;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Account\Domain\ValueObject\RegisteredPaymentMethodIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RegisterPaymentMethodTest extends TestCase
{
    /**
     * 異常系: 既存レコードがある場合はスキップして返すこと.
     *
     * @throws BindingResolutionException
     */
    public function testProcessSkipsWhenPaymentMethodAlreadyExists(): void
    {
        $monetizationAccountId = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $paymentMethodId = new PaymentMethodId('pm_existing123');

        $input = new RegisterPaymentMethodInput($monetizationAccountId, $paymentMethodId, PaymentMethodType::CARD);
        $output = new RegisterPaymentMethodOutput();

        $existing = new RegisteredPaymentMethod(
            new RegisteredPaymentMethodIdentifier(StrTestHelper::generateUuid()),
            $monetizationAccountId,
            $paymentMethodId,
            PaymentMethodType::CARD,
        );

        $repository = Mockery::mock(RegisteredPaymentMethodRepositoryInterface::class);
        $repository->shouldReceive('findByPaymentMethodId')
            ->once()
            ->with($paymentMethodId)
            ->andReturn($existing);
        $repository->shouldNotReceive('findDefaultByMonetizationAccountId');
        $repository->shouldNotReceive('save');

        $factory = Mockery::mock(RegisteredPaymentMethodFactoryInterface::class);
        $factory->shouldNotReceive('create');

        $metaResolver = Mockery::mock(PaymentMethodMetaResolverInterface::class);
        $metaResolver->shouldNotReceive('resolve');

        $this->app->instance(RegisteredPaymentMethodRepositoryInterface::class, $repository);
        $this->app->instance(RegisteredPaymentMethodFactoryInterface::class, $factory);
        $this->app->instance(PaymentMethodMetaResolverInterface::class, $metaResolver);

        $useCase = $this->app->make(RegisterPaymentMethodInterface::class);
        $useCase->process($input, $output);

        $result = $output->toArray();
        $this->assertTrue($result['skipped']);
    }

    /**
     * 正常系: 新規登録かつデフォルト登録済みなし → markAsDefault() が呼ばれること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessMarksAsDefaultWhenNoDefaultExists(): void
    {
        $monetizationAccountId = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $paymentMethodId = new PaymentMethodId('pm_new12345');

        $input = new RegisterPaymentMethodInput($monetizationAccountId, $paymentMethodId, PaymentMethodType::CARD);
        $output = new RegisterPaymentMethodOutput();

        $newPaymentMethod = new RegisteredPaymentMethod(
            new RegisteredPaymentMethodIdentifier(StrTestHelper::generateUuid()),
            $monetizationAccountId,
            $paymentMethodId,
            PaymentMethodType::CARD,
        );

        $repository = Mockery::mock(RegisteredPaymentMethodRepositoryInterface::class);
        $repository->shouldReceive('findByPaymentMethodId')
            ->once()
            ->with($paymentMethodId)
            ->andReturnNull();
        $repository->shouldReceive('findDefaultByMonetizationAccountId')
            ->once()
            ->with($monetizationAccountId)
            ->andReturnNull();
        $repository->shouldReceive('save')
            ->once()
            ->withArgs(function (RegisteredPaymentMethod $pm) {
                return $pm->isDefault() === true;
            });

        $factory = Mockery::mock(RegisteredPaymentMethodFactoryInterface::class);
        $factory->shouldReceive('create')
            ->once()
            ->with($monetizationAccountId, $paymentMethodId, PaymentMethodType::CARD)
            ->andReturn($newPaymentMethod);

        $metaResolver = Mockery::mock(PaymentMethodMetaResolverInterface::class);
        $metaResolver->shouldReceive('resolve')
            ->once()
            ->with($paymentMethodId)
            ->andReturnNull();

        $this->app->instance(RegisteredPaymentMethodRepositoryInterface::class, $repository);
        $this->app->instance(RegisteredPaymentMethodFactoryInterface::class, $factory);
        $this->app->instance(PaymentMethodMetaResolverInterface::class, $metaResolver);

        $useCase = $this->app->make(RegisterPaymentMethodInterface::class);
        $useCase->process($input, $output);

        $result = $output->toArray();
        $this->assertFalse($result['skipped']);
        $this->assertTrue($result['isDefault']);
    }

    /**
     * 正常系: 新規登録かつデフォルト登録済みあり → markAsDefault() が呼ばれないこと.
     *
     * @throws BindingResolutionException
     */
    public function testProcessDoesNotMarkAsDefaultWhenDefaultAlreadyExists(): void
    {
        $monetizationAccountId = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $paymentMethodId = new PaymentMethodId('pm_new45678');

        $input = new RegisterPaymentMethodInput($monetizationAccountId, $paymentMethodId, PaymentMethodType::CARD);
        $output = new RegisterPaymentMethodOutput();

        $newPaymentMethod = new RegisteredPaymentMethod(
            new RegisteredPaymentMethodIdentifier(StrTestHelper::generateUuid()),
            $monetizationAccountId,
            $paymentMethodId,
            PaymentMethodType::CARD,
        );

        $existingDefault = new RegisteredPaymentMethod(
            new RegisteredPaymentMethodIdentifier(StrTestHelper::generateUuid()),
            $monetizationAccountId,
            new PaymentMethodId('pm_default'),
            PaymentMethodType::CARD,
            null,
            true,
        );

        $repository = Mockery::mock(RegisteredPaymentMethodRepositoryInterface::class);
        $repository->shouldReceive('findByPaymentMethodId')
            ->once()
            ->with($paymentMethodId)
            ->andReturnNull();
        $repository->shouldReceive('findDefaultByMonetizationAccountId')
            ->once()
            ->with($monetizationAccountId)
            ->andReturn($existingDefault);
        $repository->shouldReceive('save')
            ->once()
            ->withArgs(function (RegisteredPaymentMethod $pm) {
                return $pm->isDefault() === false;
            });

        $factory = Mockery::mock(RegisteredPaymentMethodFactoryInterface::class);
        $factory->shouldReceive('create')
            ->once()
            ->andReturn($newPaymentMethod);

        $metaResolver = Mockery::mock(PaymentMethodMetaResolverInterface::class);
        $metaResolver->shouldReceive('resolve')
            ->once()
            ->andReturnNull();

        $this->app->instance(RegisteredPaymentMethodRepositoryInterface::class, $repository);
        $this->app->instance(RegisteredPaymentMethodFactoryInterface::class, $factory);
        $this->app->instance(PaymentMethodMetaResolverInterface::class, $metaResolver);

        $useCase = $this->app->make(RegisterPaymentMethodInterface::class);
        $useCase->process($input, $output);

        $result = $output->toArray();
        $this->assertFalse($result['skipped']);
        $this->assertFalse($result['isDefault']);
    }

    /**
     * 正常系: meta 解決成功 → updateMeta() が呼ばれること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessCallsUpdateMetaWhenMetaResolved(): void
    {
        $monetizationAccountId = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $paymentMethodId = new PaymentMethodId('pm_meta123');

        $input = new RegisterPaymentMethodInput($monetizationAccountId, $paymentMethodId, PaymentMethodType::CARD);
        $output = new RegisterPaymentMethodOutput();

        $newPaymentMethod = new RegisteredPaymentMethod(
            new RegisteredPaymentMethodIdentifier(StrTestHelper::generateUuid()),
            $monetizationAccountId,
            $paymentMethodId,
            PaymentMethodType::CARD,
        );

        $meta = new PaymentMethodMeta('Visa', '4242', 12, 2028);

        $repository = Mockery::mock(RegisteredPaymentMethodRepositoryInterface::class);
        $repository->shouldReceive('findByPaymentMethodId')->once()->andReturnNull();
        $repository->shouldReceive('findDefaultByMonetizationAccountId')->once()->andReturnNull();
        $repository->shouldReceive('save')
            ->once()
            ->withArgs(function (RegisteredPaymentMethod $pm) use ($meta) {
                return $pm->meta() === $meta;
            });

        $factory = Mockery::mock(RegisteredPaymentMethodFactoryInterface::class);
        $factory->shouldReceive('create')->once()->andReturn($newPaymentMethod);

        $metaResolver = Mockery::mock(PaymentMethodMetaResolverInterface::class);
        $metaResolver->shouldReceive('resolve')
            ->once()
            ->with($paymentMethodId)
            ->andReturn($meta);

        $this->app->instance(RegisteredPaymentMethodRepositoryInterface::class, $repository);
        $this->app->instance(RegisteredPaymentMethodFactoryInterface::class, $factory);
        $this->app->instance(PaymentMethodMetaResolverInterface::class, $metaResolver);

        $useCase = $this->app->make(RegisterPaymentMethodInterface::class);
        $useCase->process($input, $output);

        $result = $output->toArray();
        $this->assertSame('Visa', $result['brand']);
        $this->assertSame('4242', $result['last4']);
    }

    /**
     * 正常系: meta 解決 null → updateMeta() が呼ばれないこと.
     *
     * @throws BindingResolutionException
     */
    public function testProcessDoesNotCallUpdateMetaWhenMetaIsNull(): void
    {
        $monetizationAccountId = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $paymentMethodId = new PaymentMethodId('pm_nometa00');

        $input = new RegisterPaymentMethodInput($monetizationAccountId, $paymentMethodId, PaymentMethodType::CARD);
        $output = new RegisterPaymentMethodOutput();

        $newPaymentMethod = new RegisteredPaymentMethod(
            new RegisteredPaymentMethodIdentifier(StrTestHelper::generateUuid()),
            $monetizationAccountId,
            $paymentMethodId,
            PaymentMethodType::CARD,
        );

        $repository = Mockery::mock(RegisteredPaymentMethodRepositoryInterface::class);
        $repository->shouldReceive('findByPaymentMethodId')->once()->andReturnNull();
        $repository->shouldReceive('findDefaultByMonetizationAccountId')->once()->andReturnNull();
        $repository->shouldReceive('save')->once();

        $factory = Mockery::mock(RegisteredPaymentMethodFactoryInterface::class);
        $factory->shouldReceive('create')->once()->andReturn($newPaymentMethod);

        $metaResolver = Mockery::mock(PaymentMethodMetaResolverInterface::class);
        $metaResolver->shouldReceive('resolve')
            ->once()
            ->andReturnNull();

        $this->app->instance(RegisteredPaymentMethodRepositoryInterface::class, $repository);
        $this->app->instance(RegisteredPaymentMethodFactoryInterface::class, $factory);
        $this->app->instance(PaymentMethodMetaResolverInterface::class, $metaResolver);

        $useCase = $this->app->make(RegisterPaymentMethodInterface::class);
        $useCase->process($input, $output);

        $result = $output->toArray();
        $this->assertNull($result['brand']);
        $this->assertNull($result['last4']);
    }
}
