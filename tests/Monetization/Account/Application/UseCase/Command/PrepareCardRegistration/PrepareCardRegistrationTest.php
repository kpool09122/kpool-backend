<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\UseCase\Command\PrepareCardRegistration;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Monetization\Account\Application\Service\CardRegistrationPreparation;
use Source\Monetization\Account\Application\UseCase\Command\PrepareCardRegistration\PrepareCardRegistration;
use Source\Monetization\Account\Application\UseCase\Command\PrepareCardRegistration\PrepareCardRegistrationInput;
use Source\Monetization\Account\Application\UseCase\Command\PrepareCardRegistration\PrepareCardRegistrationOutput;
use Source\Monetization\Account\Domain\ValueObject\PaymentCustomerId;
use Source\Monetization\Account\Domain\ValueObject\SetupIntentIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;
use Tests\Helper\PaymentCardFixture;

class PrepareCardRegistrationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testRejectsMissingAccountBeforeExternalSideEffects(): void
    {
        $fixture = new PaymentCardFixture();
        $fixture->monetizationAccountRepository->shouldReceive('findByAccountIdentifier')->once()->andReturnNull();
        $useCase = new PrepareCardRegistration($fixture->monetizationAccountRepository, $fixture->principalRepository, $fixture->principalReachability, $fixture->policyEvaluator, $fixture->stepUpAuthenticationStorageService, $fixture->cardGateway);
        $this->expectException(UnauthorizedException::class);
        $useCase->process(new PrepareCardRegistrationInput($fixture->account->accountIdentifier(), $fixture->principalId), new PrepareCardRegistrationOutput());
    }

    public function testRejectsDeniedPermissionBeforeExternalSideEffects(): void
    {
        $fixture = new PaymentCardFixture();
        $fixture->allowAuthorization(Action::CARD_REGISTRATION_PREPARE, false);
        $useCase = new PrepareCardRegistration($fixture->monetizationAccountRepository, $fixture->principalRepository, $fixture->principalReachability, $fixture->policyEvaluator, $fixture->stepUpAuthenticationStorageService, $fixture->cardGateway);
        $this->expectException(UnauthorizedException::class);
        $useCase->process(new PrepareCardRegistrationInput($fixture->account->accountIdentifier(), $fixture->principalId), new PrepareCardRegistrationOutput());
    }

    public function testRejectsMissingStepUpBeforeExternalSideEffects(): void
    {
        $fixture = new PaymentCardFixture();
        $fixture->allowAuthorization(Action::CARD_REGISTRATION_PREPARE);
        $fixture->stepUpAuthenticationStorageService->shouldReceive('requireValid')->once()->andThrow(new StepUpAuthenticationRequiredException());
        $useCase = new PrepareCardRegistration($fixture->monetizationAccountRepository, $fixture->principalRepository, $fixture->principalReachability, $fixture->policyEvaluator, $fixture->stepUpAuthenticationStorageService, $fixture->cardGateway);
        $this->expectException(StepUpAuthenticationRequiredException::class);
        $useCase->process(new PrepareCardRegistrationInput($fixture->account->accountIdentifier(), $fixture->principalId), new PrepareCardRegistrationOutput());
    }

    public function testCreatesAndPersistsCustomerBeforeCreatingSetupIntent(): void
    {
        $fixture = new PaymentCardFixture(false);
        $fixture->allowAuthorization(Action::CARD_REGISTRATION_PREPARE);
        $fixture->allowStepUp();
        $customerId = new PaymentCustomerId('cus_test123');
        $preparation = new CardRegistrationPreparation(new SetupIntentIdentifier('seti_test123'), 'secret_test');
        $fixture->cardGateway->shouldReceive('createCustomer')->with($fixture->accountId)->once()->ordered()->andReturn($customerId);
        $fixture->monetizationAccountRepository->shouldReceive('save')->with($fixture->account)->once();
        $fixture->cardGateway->shouldReceive('prepareRegistration')->with($customerId)->once()->ordered()->andReturn($preparation);
        $useCase = new PrepareCardRegistration($fixture->monetizationAccountRepository, $fixture->principalRepository, $fixture->principalReachability, $fixture->policyEvaluator, $fixture->stepUpAuthenticationStorageService, $fixture->cardGateway);
        $output = new PrepareCardRegistrationOutput();
        $useCase->process(new PrepareCardRegistrationInput($fixture->account->accountIdentifier(), $fixture->principalId), $output);
        self::assertSame($customerId, $fixture->account->stripeCustomerId());
        self::assertSame(['setupIntentIdentifier' => 'seti_test123', 'clientSecret' => 'secret_test'], $output->toArray());
    }
}
