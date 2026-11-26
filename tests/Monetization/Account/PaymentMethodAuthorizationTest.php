<?php

declare(strict_types=1);

namespace Tests\Monetization\Account;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Application\UseCase\Command\DeletePaymentMethod\DeletePaymentMethod;
use Source\Monetization\Account\Application\UseCase\Command\DeletePaymentMethod\DeletePaymentMethodInput;
use Source\Monetization\Account\Application\UseCase\Command\DeletePaymentMethod\DeletePaymentMethodOutput;
use Source\Monetization\Account\Application\UseCase\Command\PrepareCardRegistration\PrepareCardRegistration;
use Source\Monetization\Account\Application\UseCase\Command\PrepareCardRegistration\PrepareCardRegistrationInput;
use Source\Monetization\Account\Application\UseCase\Command\PrepareCardRegistration\PrepareCardRegistrationOutput;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethod;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodInput;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodOutput;
use Source\Monetization\Account\Application\UseCase\Query\GetPaymentMethod\GetPaymentMethodInput;
use Source\Monetization\Account\Application\UseCase\Query\ListPaymentMethods\ListPaymentMethodsInput;
use Source\Monetization\Account\Domain\Factory\PaymentMethodArchiveFactoryInterface;
use Source\Monetization\Account\Domain\Factory\PaymentMethodFactoryInterface;
use Source\Monetization\Account\Domain\Repository\PaymentMethodArchiveRepositoryInterface;
use Source\Monetization\Account\Domain\Repository\PaymentMethodRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Account\Domain\ValueObject\SetupIntentIdentifier;
use Source\Monetization\Account\Infrastructure\Query\GetPaymentMethod;
use Source\Monetization\Account\Infrastructure\Query\ListPaymentMethods;
use Source\Monetization\Principal\Domain\Entity\Principal;
use Source\Monetization\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Monetization\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Monetization\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Monetization\Principal\Infrastructure\Service\PolicyEvaluator;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\PaymentCardFixture;
use Tests\Helper\StrTestHelper;

class PaymentMethodAuthorizationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @return iterable<string, array{string, string}> */
    public static function deniedOperations(): iterable
    {
        foreach (['setup', 'register', 'delete', 'list', 'get'] as $operation) {
            foreach (['missing', 'foreign', 'unreachable', 'suspended', 'forced_stop', 'no_permission'] as $reason) {
                yield $operation . ' ' . $reason => [$operation, $reason];
            }
        }
    }

    /** @param 'setup'|'register'|'delete'|'list'|'get' $operation */
    #[DataProvider('deniedOperations')]
    public function testRejectsUnauthorizedActorBeforeReadingCardsOrChangingExternalState(string $operation, string $reason): void
    {
        $fixture = new PaymentCardFixture();
        $fixture->monetizationAccountRepository->shouldReceive('findByAccountIdentifier')->with($fixture->account->accountIdentifier())->once()->andReturn($fixture->account);
        $principal = $reason === 'missing' ? null : new Principal(
            $fixture->principalId,
            $fixture->identityId,
            $reason === 'foreign' ? new AccountIdentifier(StrTestHelper::generateUuid()) : $fixture->account->accountIdentifier(),
            match ($reason) {
                'suspended' => PrincipalStatus::SUSPENDED,
                'forced_stop' => PrincipalStatus::FORCED_STOP,
                default => PrincipalStatus::ACTIVE,
            },
        );
        $fixture->principalRepository->shouldReceive('findById')->with($fixture->principalId)->once()->andReturn($principal);
        if (! in_array($reason, ['missing', 'foreign'], true)) {
            $fixture->principalReachability->shouldReceive('canAccess')->with($fixture->identityId, $fixture->account->accountIdentifier())->once()->andReturn($reason !== 'unreachable');
        }
        $principalGroupRepository = PaymentCardFixture::mock(PrincipalGroupRepositoryInterface::class);
        if ($reason === 'no_permission') {
            $principalGroupRepository->shouldReceive('findByPrincipalId')->with($fixture->principalId)->once()->andReturn([]);
        }
        $policyEvaluator = new PolicyEvaluator($principalGroupRepository, PaymentCardFixture::mock(RoleRepositoryInterface::class), PaymentCardFixture::mock(PolicyRepositoryInterface::class));
        $paymentMethodRepository = PaymentCardFixture::mock(PaymentMethodRepositoryInterface::class);
        $id = new PaymentMethodIdentifier(StrTestHelper::generateUuid());
        $accountId = $fixture->account->accountIdentifier();
        $principalId = $fixture->principalId;
        $this->expectException(UnauthorizedException::class);
        match ($operation) {
            'setup' => (new PrepareCardRegistration($fixture->monetizationAccountRepository, $fixture->principalRepository, $fixture->principalReachability, $policyEvaluator, $fixture->stepUpAuthenticationStorageService, $fixture->cardGateway))->process(new PrepareCardRegistrationInput($accountId, $principalId), new PrepareCardRegistrationOutput()),
            'register' => (new RegisterPaymentMethod($paymentMethodRepository, PaymentCardFixture::mock(PaymentMethodFactoryInterface::class), $fixture->cardGateway, $fixture->monetizationAccountRepository, $fixture->principalRepository, $fixture->principalReachability, $policyEvaluator, $fixture->stepUpAuthenticationStorageService))->process(new RegisterPaymentMethodInput($accountId, new SetupIntentIdentifier('seti_test123'), $principalId), new RegisterPaymentMethodOutput()),
            'delete' => (new DeletePaymentMethod($fixture->monetizationAccountRepository, $fixture->principalRepository, $fixture->principalReachability, $policyEvaluator, $fixture->stepUpAuthenticationStorageService, $fixture->cardGateway, $paymentMethodRepository, PaymentCardFixture::mock(PaymentMethodArchiveRepositoryInterface::class), PaymentCardFixture::mock(PaymentMethodArchiveFactoryInterface::class)))->process(new DeletePaymentMethodInput($accountId, $id, $principalId), new DeletePaymentMethodOutput()),
            'list' => (new ListPaymentMethods($fixture->monetizationAccountRepository, $fixture->principalRepository, $fixture->principalReachability, $policyEvaluator))->process(new ListPaymentMethodsInput($accountId, $principalId)),
            'get' => (new GetPaymentMethod($fixture->monetizationAccountRepository, $fixture->principalRepository, $fixture->principalReachability, $policyEvaluator))->process(new GetPaymentMethodInput($accountId, $id, $principalId)),
        };
    }
}
