<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\UseCase\Command\DeletePaymentMethod;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Monetization\Account\Application\Exception\PaymentMethodNotFoundException;
use Source\Monetization\Account\Application\UseCase\Command\DeletePaymentMethod\DeletePaymentMethod;
use Source\Monetization\Account\Application\UseCase\Command\DeletePaymentMethod\DeletePaymentMethodInput;
use Source\Monetization\Account\Application\UseCase\Command\DeletePaymentMethod\DeletePaymentMethodOutput;
use Source\Monetization\Account\Domain\Entity\MonetizationAccount;
use Source\Monetization\Account\Domain\Entity\PaymentMethod;
use Source\Monetization\Account\Domain\Entity\PaymentMethodArchive;
use Source\Monetization\Account\Domain\Factory\PaymentMethodArchiveFactoryInterface;
use Source\Monetization\Account\Domain\Repository\PaymentMethodArchiveRepositoryInterface;
use Source\Monetization\Account\Domain\Repository\PaymentMethodRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentCustomerId;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Account\Infrastructure\Factory\PaymentMethodArchiveFactory;
use Source\Monetization\Account\Infrastructure\Repository\PaymentMethodArchiveRepository;
use Source\Monetization\Account\Infrastructure\Repository\PaymentMethodRepository;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Tests\Helper\CreateMonetizationAccount;
use Tests\Helper\CreatePaymentMethod;
use Tests\Helper\PaymentCardFixture;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DeletePaymentMethodTest extends TestCase
{
    public function testRejectsMissingAccountBeforeExternalSideEffects(): void
    {
        $fixture = new PaymentCardFixture();
        $fixture->monetizationAccountRepository->shouldReceive('findByAccountIdentifier')->once()->andReturnNull();
        $useCase = new DeletePaymentMethod($fixture->monetizationAccountRepository, $fixture->principalRepository, $fixture->principalReachability, $fixture->policyEvaluator, $fixture->stepUpAuthenticationStorageService, $fixture->cardGateway, PaymentCardFixture::mock(PaymentMethodRepositoryInterface::class), PaymentCardFixture::mock(PaymentMethodArchiveRepositoryInterface::class), PaymentCardFixture::mock(PaymentMethodArchiveFactoryInterface::class));
        $this->expectException(UnauthorizedException::class);
        $useCase->process(new DeletePaymentMethodInput($fixture->account->accountIdentifier(), new PaymentMethodIdentifier(StrTestHelper::generateUuid()), $fixture->principalId), new DeletePaymentMethodOutput());
    }

    public function testRejectsDeniedPermissionBeforeExternalSideEffects(): void
    {
        $fixture = new PaymentCardFixture();
        $fixture->allowAuthorization(Action::PAYMENT_METHOD_DELETE, false);
        $useCase = new DeletePaymentMethod($fixture->monetizationAccountRepository, $fixture->principalRepository, $fixture->principalReachability, $fixture->policyEvaluator, $fixture->stepUpAuthenticationStorageService, $fixture->cardGateway, PaymentCardFixture::mock(PaymentMethodRepositoryInterface::class), PaymentCardFixture::mock(PaymentMethodArchiveRepositoryInterface::class), PaymentCardFixture::mock(PaymentMethodArchiveFactoryInterface::class));
        $this->expectException(UnauthorizedException::class);
        $useCase->process(new DeletePaymentMethodInput($fixture->account->accountIdentifier(), new PaymentMethodIdentifier(StrTestHelper::generateUuid()), $fixture->principalId), new DeletePaymentMethodOutput());
    }

    public function testRejectsMissingStepUpBeforeExternalSideEffects(): void
    {
        $fixture = new PaymentCardFixture();
        $fixture->allowAuthorization(Action::PAYMENT_METHOD_DELETE);
        $fixture->stepUpAuthenticationStorageService->shouldReceive('requireValid')->once()->andThrow(new StepUpAuthenticationRequiredException());
        $useCase = new DeletePaymentMethod($fixture->monetizationAccountRepository, $fixture->principalRepository, $fixture->principalReachability, $fixture->policyEvaluator, $fixture->stepUpAuthenticationStorageService, $fixture->cardGateway, PaymentCardFixture::mock(PaymentMethodRepositoryInterface::class), PaymentCardFixture::mock(PaymentMethodArchiveRepositoryInterface::class), PaymentCardFixture::mock(PaymentMethodArchiveFactoryInterface::class));
        $this->expectException(StepUpAuthenticationRequiredException::class);
        $useCase->process(new DeletePaymentMethodInput($fixture->account->accountIdentifier(), new PaymentMethodIdentifier(StrTestHelper::generateUuid()), $fixture->principalId), new DeletePaymentMethodOutput());
    }

    #[Group('useDb')]
    public function testDeletingDefaultPromotesLowestIdentifierRemainingActiveCard(): void
    {
        $id = new PaymentMethodIdentifier(StrTestHelper::generateUuid());
        CreatePaymentMethod::create((string) $id, ['is_default' => true]);
        $repository = new PaymentMethodRepository();
        $method = $repository->findById($id);
        self::assertNotNull($method);
        $accountId = (string) $method->monetizationAccountIdentifier();
        $remainingA = StrTestHelper::generateUuid();
        $remainingB = StrTestHelper::generateUuid();
        $inactive = StrTestHelper::generateUuid();
        CreatePaymentMethod::create($remainingA, ['monetization_account_id' => $accountId]);
        CreatePaymentMethod::create($remainingB, ['monetization_account_id' => $accountId]);
        CreatePaymentMethod::create($inactive, ['monetization_account_id' => $accountId,'status' => 'inactive']);
        $fixture = new PaymentCardFixture();
        $fixture->accountId = $method->monetizationAccountIdentifier();
        $fixture->account = new MonetizationAccount($fixture->accountId, $fixture->account->accountIdentifier(), [], new PaymentCustomerId('cus_test123'), null);
        $fixture->allowAuthorization(Action::PAYMENT_METHOD_DELETE);
        $fixture->allowStepUp();
        $fixture->cardGateway->shouldReceive('detach')->once();
        $uuidGenerator = PaymentCardFixture::mock(UuidGeneratorInterface::class);
        $uuidGenerator->shouldReceive('generate')->once()->andReturn(StrTestHelper::generateUuid());
        $useCase = new DeletePaymentMethod($fixture->monetizationAccountRepository, $fixture->principalRepository, $fixture->principalReachability, $fixture->policyEvaluator, $fixture->stepUpAuthenticationStorageService, $fixture->cardGateway, $repository, new PaymentMethodArchiveRepository(), new PaymentMethodArchiveFactory($uuidGenerator));
        DB::transaction(fn () => $useCase->process(new DeletePaymentMethodInput($fixture->account->accountIdentifier(), $id, $fixture->principalId), new DeletePaymentMethodOutput()));
        self::assertNull($repository->findById($id));
        self::assertSame(1, DB::table('monetization_payment_methods')->where('monetization_account_id', $accountId)->where('is_default', true)->count());
        self::assertTrue(DB::table('monetization_payment_methods')->where('id', min($remainingA, $remainingB))->value('is_default'));
        self::assertFalse(DB::table('monetization_payment_methods')->where('id', $inactive)->value('is_default'));
    }

    public function testRejectsForeignCardBeforeDetachOrArchive(): void
    {
        $fixture = new PaymentCardFixture();
        $fixture->allowAuthorization(Action::PAYMENT_METHOD_DELETE);
        $fixture->allowStepUp();
        $id = new PaymentMethodIdentifier(StrTestHelper::generateUuid());
        $method = new PaymentMethod($id, new MonetizationAccountIdentifier(StrTestHelper::generateUuid()), new ExternalPaymentMethodId('pm_test12345'), PaymentMethodType::CARD);
        $repository = PaymentCardFixture::mock(PaymentMethodRepositoryInterface::class);
        $repository->shouldReceive('findById')->with($id)->once()->andReturn($method);
        $useCase = new DeletePaymentMethod($fixture->monetizationAccountRepository, $fixture->principalRepository, $fixture->principalReachability, $fixture->policyEvaluator, $fixture->stepUpAuthenticationStorageService, $fixture->cardGateway, $repository, PaymentCardFixture::mock(PaymentMethodArchiveRepositoryInterface::class), PaymentCardFixture::mock(PaymentMethodArchiveFactoryInterface::class));
        $this->expectException(PaymentMethodNotFoundException::class);
        $useCase->process(new DeletePaymentMethodInput($fixture->account->accountIdentifier(), $id, $fixture->principalId), new DeletePaymentMethodOutput());
    }

    /** @return iterable<string, array{bool}> */
    public static function persistenceFailures(): iterable
    {
        yield 'archive write' => [false];
        yield 'current delete' => [true];
    }

    #[Group('useDb')]
    #[DataProvider('persistenceFailures')]
    public function testDeleteLastCardAndRetryAfterPersistenceFailure(bool $failOnDelete): void
    {
        $fixture = new PaymentCardFixture();
        $fixture->allowAuthorization(Action::PAYMENT_METHOD_DELETE, times: 2);
        $fixture->allowStepUp();
        $fixture->allowStepUp();
        CreateMonetizationAccount::create((string) $fixture->accountId);
        $id = new PaymentMethodIdentifier(StrTestHelper::generateUuid());
        CreatePaymentMethod::create((string) $id, ['monetization_account_id' => (string) $fixture->accountId, 'brand' => 'visa','last4' => '4242','is_default' => true]);
        $repository = new PaymentMethodRepository();
        $method = $repository->findById($id);
        self::assertNotNull($method);
        $fixture->cardGateway->shouldReceive('detach')->withArgs(fn (PaymentCustomerId $customer, ExternalPaymentMethodId $external) => (string) $customer === 'cus_test123' && (string) $external === (string) $method->externalPaymentMethodId())->twice();
        $uuidGenerator = PaymentCardFixture::mock(UuidGeneratorInterface::class);
        $uuidGenerator->shouldReceive('generate')->andReturn(StrTestHelper::generateUuid());
        $factory = new PaymentMethodArchiveFactory($uuidGenerator);
        $failingArchiveRepository = PaymentCardFixture::mock(PaymentMethodArchiveRepositoryInterface::class);
        $failingArchiveRepository->shouldReceive('save')->times($failOnDelete ? 0 : 1)->andReturnUsing(function (PaymentMethodArchive $archive): void {
            (new PaymentMethodArchiveRepository())->save($archive);
            self::assertSame(1, DB::table('monetization_payment_method_archives')->count());

            throw new RuntimeException('after actual archive write');
        });
        $failingPaymentMethodRepository = $repository;
        if ($failOnDelete) {
            $failingArchiveRepository = new PaymentMethodArchiveRepository();
            $failingPaymentMethodRepository = PaymentCardFixture::mock(PaymentMethodRepositoryInterface::class);
            $failingPaymentMethodRepository->shouldReceive('findById')->with($id)->once()->andReturn($method);
            $failingPaymentMethodRepository->shouldReceive('delete')->withArgs(fn (PaymentMethodIdentifier $value): bool => (string) $value === (string) $id)->once()->andReturnUsing(function () use ($repository, $id): void {
                $repository->delete($id);
                self::assertNull($repository->findById($id));

                throw new RuntimeException('after actual archive write');
            });
        }
        $input = new DeletePaymentMethodInput($fixture->account->accountIdentifier(), $id, $fixture->principalId);
        $failed = new DeletePaymentMethod($fixture->monetizationAccountRepository, $fixture->principalRepository, $fixture->principalReachability, $fixture->policyEvaluator, $fixture->stepUpAuthenticationStorageService, $fixture->cardGateway, $failingPaymentMethodRepository, $failingArchiveRepository, $factory);

        try {
            DB::transaction(fn () => $failed->process($input, new DeletePaymentMethodOutput()));
            self::fail('Expected archive failure');
        } catch (RuntimeException $e) {
            self::assertSame('after actual archive write', $e->getMessage());
        }
        self::assertSame(0, DB::table('monetization_payment_method_archives')->count());
        self::assertNotNull($repository->findById($id));
        $retry = new DeletePaymentMethod($fixture->monetizationAccountRepository, $fixture->principalRepository, $fixture->principalReachability, $fixture->policyEvaluator, $fixture->stepUpAuthenticationStorageService, $fixture->cardGateway, $repository, new PaymentMethodArchiveRepository(), $factory);
        DB::transaction(fn () => $retry->process($input, new DeletePaymentMethodOutput()));
        self::assertNull($repository->findById($id));
        self::assertSame([], $repository->findByMonetizationAccountId($fixture->accountId));
        self::assertNotNull((new PaymentMethodArchiveRepository())->findByPaymentMethodIdentifier($id));
    }
}
