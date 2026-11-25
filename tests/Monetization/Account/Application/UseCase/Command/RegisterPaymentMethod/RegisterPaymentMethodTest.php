<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Account\Principal\Application\Service\PrincipalReachabilityInterface;
use Source\Monetization\Account\Application\Exception\PaymentMethodAlreadyExistsException;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodInput;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodInterface;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodOutput;
use Source\Monetization\Account\Domain\Entity\MonetizationAccount;
use Source\Monetization\Account\Domain\Entity\PaymentMethod;
use Source\Monetization\Account\Domain\Factory\PaymentMethodFactoryInterface;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;
use Source\Monetization\Account\Domain\Repository\PaymentMethodRepositoryInterface;
use Source\Monetization\Account\Domain\Service\PaymentMethodMetaResolverInterface;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodMeta;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Principal\Domain\Entity\Principal;
use Source\Monetization\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Monetization\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Monetization\Principal\Domain\ValueObject\Resource;
use Source\Monetization\Principal\Domain\ValueObject\ResourceType;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RegisterPaymentMethodTest extends TestCase
{
    private ?PrincipalIdentifier $operatorPrincipalIdentifier = null;
    private ?AccountIdentifier $accountIdentifier = null;

    private function operatorPrincipalIdentifier(): PrincipalIdentifier
    {
        return $this->operatorPrincipalIdentifier ??= new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000001');
    }

    private function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier ??= new AccountIdentifier('019c9b4c-0000-7000-8000-000000000002');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $principal = new Principal($this->operatorPrincipalIdentifier(), new IdentityIdentifier(StrTestHelper::generateUuid()), $this->accountIdentifier(), PrincipalStatus::ACTIVE);
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->with($this->operatorPrincipalIdentifier())->andReturn($principal)->byDefault();
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $monetizationAccountRepository = Mockery::mock(MonetizationAccountRepositoryInterface::class);
        $monetizationAccountRepository->shouldReceive('findById')->andReturnUsing(fn (MonetizationAccountIdentifier $id) => new MonetizationAccount($id, $this->accountIdentifier(), [], null, null))->byDefault();
        $this->app()->instance(MonetizationAccountRepositoryInterface::class, $monetizationAccountRepository);
        $principalReachability = Mockery::mock(PrincipalReachabilityInterface::class);
        $principalReachability->shouldReceive('canAccess')->with($principal->identityIdentifier(), $this->accountIdentifier())->andReturnTrue()->byDefault();
        $this->app()->instance(PrincipalReachabilityInterface::class, $principalReachability);
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->with($principal, Action::PAYMENT_METHOD_REGISTER, Mockery::on(fn (Resource $resource) => $resource->type() === ResourceType::PAYMENT_METHOD && (string) $resource->accountIdentifier() === (string) $this->accountIdentifier()))->andReturnTrue()->byDefault();
        $this->app()->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
    }

    #[DataProvider('unauthorizedProvider')]
    public function testRejectsUnauthorizedRegistrationBeforeLookingUpPaymentMethods(string $reason): void
    {
        $id = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        if ($reason === 'missing principal') {
            $repository = Mockery::mock(PrincipalRepositoryInterface::class);
            $repository->shouldReceive('findById')->andReturnNull();
            $this->app()->instance(PrincipalRepositoryInterface::class, $repository);
        } elseif ($reason === 'missing account' || $reason === 'different account') {
            $repository = Mockery::mock(MonetizationAccountRepositoryInterface::class);
            $repository->shouldReceive('findById')->andReturn($reason === 'missing account' ? null : new MonetizationAccount($id, new AccountIdentifier(StrTestHelper::generateUuid()), [], null, null));
            $this->app()->instance(MonetizationAccountRepositoryInterface::class, $repository);
        } elseif ($reason === 'unreachable') {
            $principalReachability = Mockery::mock(PrincipalReachabilityInterface::class);
            $principalReachability->shouldReceive('canAccess')->andReturnFalse();
            $this->app()->instance(PrincipalReachabilityInterface::class, $principalReachability);
        } else {
            $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
            $policyEvaluator->shouldReceive('evaluate')->andReturnFalse();
            $this->app()->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        }
        $paymentMethodRepository = Mockery::mock(PaymentMethodRepositoryInterface::class);
        $paymentMethodRepository->shouldNotReceive('findByExternalPaymentMethodId');
        $paymentMethodRepository->shouldNotReceive('save');
        $this->app()->instance(PaymentMethodRepositoryInterface::class, $paymentMethodRepository);
        $paymentMethodFactory = Mockery::mock(PaymentMethodFactoryInterface::class);
        $paymentMethodFactory->shouldNotReceive('create');
        $this->app()->instance(PaymentMethodFactoryInterface::class, $paymentMethodFactory);
        $paymentMethodMetaResolver = Mockery::mock(PaymentMethodMetaResolverInterface::class);
        $paymentMethodMetaResolver->shouldNotReceive('resolve');
        $this->app()->instance(PaymentMethodMetaResolverInterface::class, $paymentMethodMetaResolver);

        $this->expectException(UnauthorizedException::class);
        $this->app()->make(RegisterPaymentMethodInterface::class)->process(
            new RegisterPaymentMethodInput($id, new ExternalPaymentMethodId('pm_existing123'), PaymentMethodType::CARD, $this->operatorPrincipalIdentifier()),
            new RegisterPaymentMethodOutput(),
        );
    }

    /** @return array<string, array{string}> */
    public static function unauthorizedProvider(): array
    {
        return array_combine(
            $reasons = ['missing principal', 'missing account', 'different account', 'unreachable', 'policy denied'],
            array_map(static fn (string $reason): array => [$reason], $reasons),
        );
    }

    /**
     * 異常系: 既存レコードがある場合は例外を投げること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessThrowsWhenPaymentMethodAlreadyExists(): void
    {
        $monetizationAccountId = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $externalPaymentMethodId = new ExternalPaymentMethodId('pm_existing123');

        $input = new RegisterPaymentMethodInput($monetizationAccountId, $externalPaymentMethodId, PaymentMethodType::CARD, $this->operatorPrincipalIdentifier());
        $output = new RegisterPaymentMethodOutput();

        $existing = new PaymentMethod(
            new PaymentMethodIdentifier(StrTestHelper::generateUuid()),
            $monetizationAccountId,
            $externalPaymentMethodId,
            PaymentMethodType::CARD,
        );

        $repository = Mockery::mock(PaymentMethodRepositoryInterface::class);
        $repository->shouldReceive('findByExternalPaymentMethodId')
            ->once()
            ->with($externalPaymentMethodId, $monetizationAccountId)
            ->andReturn($existing);
        $repository->shouldNotReceive('findDefaultByMonetizationAccountId');
        $repository->shouldNotReceive('save');

        $factory = Mockery::mock(PaymentMethodFactoryInterface::class);
        $factory->shouldNotReceive('create');

        $metaResolver = Mockery::mock(PaymentMethodMetaResolverInterface::class);
        $metaResolver->shouldNotReceive('resolve');

        $this->app()->instance(PaymentMethodRepositoryInterface::class, $repository);
        $this->app()->instance(PaymentMethodFactoryInterface::class, $factory);
        $this->app()->instance(PaymentMethodMetaResolverInterface::class, $metaResolver);

        $useCase = $this->app()->make(RegisterPaymentMethodInterface::class);
        $this->expectException(PaymentMethodAlreadyExistsException::class);
        $useCase->process($input, $output);
    }

    /**
     * 正常系: 新規登録かつデフォルト登録済みなし → markAsDefault() が呼ばれること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessMarksAsDefaultWhenNoDefaultExists(): void
    {
        $monetizationAccountId = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $externalPaymentMethodId = new ExternalPaymentMethodId('pm_new12345');

        $input = new RegisterPaymentMethodInput($monetizationAccountId, $externalPaymentMethodId, PaymentMethodType::CARD, $this->operatorPrincipalIdentifier());
        $output = new RegisterPaymentMethodOutput();

        $newPaymentMethod = new PaymentMethod(
            new PaymentMethodIdentifier(StrTestHelper::generateUuid()),
            $monetizationAccountId,
            $externalPaymentMethodId,
            PaymentMethodType::CARD,
        );

        $repository = Mockery::mock(PaymentMethodRepositoryInterface::class);
        $repository->shouldReceive('findByExternalPaymentMethodId')
            ->once()
            ->with($externalPaymentMethodId, $monetizationAccountId)
            ->andReturnNull();
        $repository->shouldReceive('findDefaultByMonetizationAccountId')
            ->once()
            ->with($monetizationAccountId)
            ->andReturnNull();
        $repository->shouldReceive('save')
            ->once()
            ->withArgs(fn (PaymentMethod $pm) => $pm->isDefault() === true);

        $factory = Mockery::mock(PaymentMethodFactoryInterface::class);
        $factory->shouldReceive('create')
            ->once()
            ->with($monetizationAccountId, $externalPaymentMethodId, PaymentMethodType::CARD)
            ->andReturn($newPaymentMethod);

        $metaResolver = Mockery::mock(PaymentMethodMetaResolverInterface::class);
        $metaResolver->shouldReceive('resolve')
            ->once()
            ->with($externalPaymentMethodId)
            ->andReturnNull();

        $this->app()->instance(PaymentMethodRepositoryInterface::class, $repository);
        $this->app()->instance(PaymentMethodFactoryInterface::class, $factory);
        $this->app()->instance(PaymentMethodMetaResolverInterface::class, $metaResolver);

        $useCase = $this->app()->make(RegisterPaymentMethodInterface::class);
        $useCase->process($input, $output);

        $result = $output->toArray();
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
        $externalPaymentMethodId = new ExternalPaymentMethodId('pm_new45678');

        $input = new RegisterPaymentMethodInput($monetizationAccountId, $externalPaymentMethodId, PaymentMethodType::CARD, $this->operatorPrincipalIdentifier());
        $output = new RegisterPaymentMethodOutput();

        $newPaymentMethod = new PaymentMethod(
            new PaymentMethodIdentifier(StrTestHelper::generateUuid()),
            $monetizationAccountId,
            $externalPaymentMethodId,
            PaymentMethodType::CARD,
        );

        $existingDefault = new PaymentMethod(
            new PaymentMethodIdentifier(StrTestHelper::generateUuid()),
            $monetizationAccountId,
            new ExternalPaymentMethodId('pm_default'),
            PaymentMethodType::CARD,
            null,
            true,
        );

        $repository = Mockery::mock(PaymentMethodRepositoryInterface::class);
        $repository->shouldReceive('findByExternalPaymentMethodId')
            ->once()
            ->with($externalPaymentMethodId, $monetizationAccountId)
            ->andReturnNull();
        $repository->shouldReceive('findDefaultByMonetizationAccountId')
            ->once()
            ->with($monetizationAccountId)
            ->andReturn($existingDefault);
        $repository->shouldReceive('save')
            ->once()
            ->withArgs(fn (PaymentMethod $pm) => $pm->isDefault() === false);

        $factory = Mockery::mock(PaymentMethodFactoryInterface::class);
        $factory->shouldReceive('create')
            ->once()
            ->andReturn($newPaymentMethod);

        $metaResolver = Mockery::mock(PaymentMethodMetaResolverInterface::class);
        $metaResolver->shouldReceive('resolve')
            ->once()
            ->andReturnNull();

        $this->app()->instance(PaymentMethodRepositoryInterface::class, $repository);
        $this->app()->instance(PaymentMethodFactoryInterface::class, $factory);
        $this->app()->instance(PaymentMethodMetaResolverInterface::class, $metaResolver);

        $useCase = $this->app()->make(RegisterPaymentMethodInterface::class);
        $useCase->process($input, $output);

        $result = $output->toArray();
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
        $externalPaymentMethodId = new ExternalPaymentMethodId('pm_meta123');

        $input = new RegisterPaymentMethodInput($monetizationAccountId, $externalPaymentMethodId, PaymentMethodType::CARD, $this->operatorPrincipalIdentifier());
        $output = new RegisterPaymentMethodOutput();

        $newPaymentMethod = new PaymentMethod(
            new PaymentMethodIdentifier(StrTestHelper::generateUuid()),
            $monetizationAccountId,
            $externalPaymentMethodId,
            PaymentMethodType::CARD,
        );

        $meta = new PaymentMethodMeta('Visa', '4242', 12, 2028);

        $repository = Mockery::mock(PaymentMethodRepositoryInterface::class);
        $repository->shouldReceive('findByExternalPaymentMethodId')->once()->with($externalPaymentMethodId, $monetizationAccountId)->andReturnNull();
        $repository->shouldReceive('findDefaultByMonetizationAccountId')->once()->andReturnNull();
        $repository->shouldReceive('save')
            ->once()
            ->withArgs(fn (PaymentMethod $pm) => $pm->meta() === $meta);

        $factory = Mockery::mock(PaymentMethodFactoryInterface::class);
        $factory->shouldReceive('create')->once()->andReturn($newPaymentMethod);

        $metaResolver = Mockery::mock(PaymentMethodMetaResolverInterface::class);
        $metaResolver->shouldReceive('resolve')
            ->once()
            ->with($externalPaymentMethodId)
            ->andReturn($meta);

        $this->app()->instance(PaymentMethodRepositoryInterface::class, $repository);
        $this->app()->instance(PaymentMethodFactoryInterface::class, $factory);
        $this->app()->instance(PaymentMethodMetaResolverInterface::class, $metaResolver);

        $useCase = $this->app()->make(RegisterPaymentMethodInterface::class);
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
        $externalPaymentMethodId = new ExternalPaymentMethodId('pm_nometa00');

        $input = new RegisterPaymentMethodInput($monetizationAccountId, $externalPaymentMethodId, PaymentMethodType::CARD, $this->operatorPrincipalIdentifier());
        $output = new RegisterPaymentMethodOutput();

        $newPaymentMethod = new PaymentMethod(
            new PaymentMethodIdentifier(StrTestHelper::generateUuid()),
            $monetizationAccountId,
            $externalPaymentMethodId,
            PaymentMethodType::CARD,
        );

        $repository = Mockery::mock(PaymentMethodRepositoryInterface::class);
        $repository->shouldReceive('findByExternalPaymentMethodId')->once()->with($externalPaymentMethodId, $monetizationAccountId)->andReturnNull();
        $repository->shouldReceive('findDefaultByMonetizationAccountId')->once()->andReturnNull();
        $repository->shouldReceive('save')->once();

        $factory = Mockery::mock(PaymentMethodFactoryInterface::class);
        $factory->shouldReceive('create')->once()->andReturn($newPaymentMethod);

        $metaResolver = Mockery::mock(PaymentMethodMetaResolverInterface::class);
        $metaResolver->shouldReceive('resolve')
            ->once()
            ->andReturnNull();

        $this->app()->instance(PaymentMethodRepositoryInterface::class, $repository);
        $this->app()->instance(PaymentMethodFactoryInterface::class, $factory);
        $this->app()->instance(PaymentMethodMetaResolverInterface::class, $metaResolver);

        $useCase = $this->app()->make(RegisterPaymentMethodInterface::class);
        $useCase->process($input, $output);

        $result = $output->toArray();
        $this->assertNull($result['brand']);
        $this->assertNull($result['last4']);
    }
}
