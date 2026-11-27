<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod;

use DateTimeImmutable;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Identity\Domain\ValueObject\StepUpAuthentication;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationMethod;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Monetization\Account\Application\Service\CardGatewayInterface;
use Source\Monetization\Account\Application\Service\VerifiedCard;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethod;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodInput;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodOutput;
use Source\Monetization\Account\Domain\Entity\MonetizationAccount;
use Source\Monetization\Account\Domain\Entity\PaymentMethod;
use Source\Monetization\Account\Domain\Factory\PaymentMethodFactoryInterface;
use Source\Monetization\Account\Domain\Repository\MonetizationAccountRepositoryInterface;
use Source\Monetization\Account\Domain\Repository\PaymentMethodRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentCustomerId;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodMeta;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Account\Domain\ValueObject\SetupIntentIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\PaymentCardFixture;
use Tests\Helper\StrTestHelper;

class RegisterPaymentMethodTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testRejectsMissingAccountBeforeExternalSideEffects(): void
    {
        $fixture = new PaymentCardFixture();
        $fixture->monetizationAccountRepository->shouldReceive('findByAccountIdentifier')->once()->andReturnNull();
        $useCase = new RegisterPaymentMethod(PaymentCardFixture::mock(PaymentMethodRepositoryInterface::class), PaymentCardFixture::mock(PaymentMethodFactoryInterface::class), $fixture->cardGateway, $fixture->monetizationAccountRepository, $fixture->principalRepository, $fixture->principalReachability, $fixture->policyEvaluator, $fixture->stepUpAuthenticationStorageService);
        $this->expectException(UnauthorizedException::class);
        $useCase->process(new RegisterPaymentMethodInput($fixture->account->accountIdentifier(), new SetupIntentIdentifier('seti_test123'), $fixture->principalId), new RegisterPaymentMethodOutput());
    }

    public function testRejectsDeniedPermissionBeforeExternalSideEffects(): void
    {
        $fixture = new PaymentCardFixture();
        $fixture->allowAuthorization(Action::PAYMENT_METHOD_REGISTER, false);
        $useCase = new RegisterPaymentMethod(PaymentCardFixture::mock(PaymentMethodRepositoryInterface::class), PaymentCardFixture::mock(PaymentMethodFactoryInterface::class), $fixture->cardGateway, $fixture->monetizationAccountRepository, $fixture->principalRepository, $fixture->principalReachability, $fixture->policyEvaluator, $fixture->stepUpAuthenticationStorageService);
        $this->expectException(UnauthorizedException::class);
        $useCase->process(new RegisterPaymentMethodInput($fixture->account->accountIdentifier(), new SetupIntentIdentifier('seti_test123'), $fixture->principalId), new RegisterPaymentMethodOutput());
    }

    public function testRejectsMissingStepUpBeforeExternalSideEffects(): void
    {
        $fixture = new PaymentCardFixture();
        $fixture->allowAuthorization(Action::PAYMENT_METHOD_REGISTER);
        $fixture->stepUpAuthenticationStorageService->shouldReceive('requireValid')->once()->andThrow(new StepUpAuthenticationRequiredException());
        $useCase = new RegisterPaymentMethod(PaymentCardFixture::mock(PaymentMethodRepositoryInterface::class), PaymentCardFixture::mock(PaymentMethodFactoryInterface::class), $fixture->cardGateway, $fixture->monetizationAccountRepository, $fixture->principalRepository, $fixture->principalReachability, $fixture->policyEvaluator, $fixture->stepUpAuthenticationStorageService);
        $this->expectException(StepUpAuthenticationRequiredException::class);
        $useCase->process(new RegisterPaymentMethodInput($fixture->account->accountIdentifier(), new SetupIntentIdentifier('seti_test123'), $fixture->principalId), new RegisterPaymentMethodOutput());
    }

    public function testRegistersVerifiedCardAndReplaysWithoutDuplicateSave(): void
    {
        $accountId = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $principalId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $identityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $customerId = new PaymentCustomerId('cus_test123');
        $account = new MonetizationAccount($accountId, new AccountIdentifier(StrTestHelper::generateUuid()), [], $customerId, null);
        $intentId = new SetupIntentIdentifier('seti_test123');
        $card = new VerifiedCard(new ExternalPaymentMethodId('pm_test12345'), new PaymentMethodMeta('visa', '4242', 12, 2030));
        $method = new PaymentMethod(new PaymentMethodIdentifier(StrTestHelper::generateUuid()), $accountId, $card->externalPaymentMethodId, PaymentMethodType::CARD);
        $monetizationAccountRepository = PaymentCardFixture::mock(MonetizationAccountRepositoryInterface::class);
        $monetizationAccountRepository->shouldReceive('findByAccountIdentifier')->with($account->accountIdentifier())->twice()->andReturn($account);
        $fixture = new PaymentCardFixture();
        $fixture->principalId = $principalId;
        $fixture->identityId = $identityId;
        $fixture->account = $account;
        $fixture->allowAuthorization(Action::PAYMENT_METHOD_REGISTER, times: 2);
        $stepUpAuthenticationStorageService = PaymentCardFixture::mock(StepUpAuthenticationStorageServiceInterface::class);
        $stepUpAuthenticationStorageService->shouldReceive('requireValid')->with($identityId, StepUpAuthenticationScope::RECENT_AUTHENTICATION)->twice()->andReturn(new StepUpAuthentication($identityId, StepUpAuthenticationMethod::PASSKEY, new DateTimeImmutable(), StepUpAuthenticationScope::RECENT_AUTHENTICATION, new DateTimeImmutable('+5 minutes')));
        $cardGateway = PaymentCardFixture::mock(CardGatewayInterface::class);
        $cardGateway->shouldReceive('verify')->with($customerId, $intentId)->twice()->andReturn($card);
        $paymentMethodRepository = PaymentCardFixture::mock(PaymentMethodRepositoryInterface::class);
        $paymentMethodRepository->shouldReceive('findByExternalPaymentMethodId')->with($card->externalPaymentMethodId, $accountId)->twice()->andReturn(null, $method);
        $paymentMethodRepository->shouldReceive('findDefaultByMonetizationAccountId')->with($accountId)->once()->andReturnNull();
        $paymentMethodRepository->shouldReceive('save')->once()->with($method);
        $paymentMethodFactory = PaymentCardFixture::mock(PaymentMethodFactoryInterface::class);
        $paymentMethodFactory->shouldReceive('create')->with($accountId, $card->externalPaymentMethodId, PaymentMethodType::CARD)->once()->andReturn($method);
        $useCase = new RegisterPaymentMethod($paymentMethodRepository, $paymentMethodFactory, $cardGateway, $monetizationAccountRepository, $fixture->principalRepository, $fixture->principalReachability, $fixture->policyEvaluator, $stepUpAuthenticationStorageService);
        $input = new RegisterPaymentMethodInput($account->accountIdentifier(), $intentId, $principalId);
        $output = new RegisterPaymentMethodOutput();
        $useCase->process($input, $output);
        self::assertSame('visa', $output->toArray()['brand']);
        self::assertSame('4242', $output->toArray()['last4']);
        self::assertTrue($output->toArray()['isDefault']);
        $replayed = new RegisterPaymentMethodOutput();
        $useCase->process($input, $replayed);
        self::assertSame($output->toArray(), $replayed->toArray());
    }
}
