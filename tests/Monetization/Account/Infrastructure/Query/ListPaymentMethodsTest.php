<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Infrastructure\Query;

use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Account\Application\UseCase\Query\ListPaymentMethods\ListPaymentMethodsInput;
use Source\Monetization\Account\Infrastructure\Query\ListPaymentMethods;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateMonetizationAccount;
use Tests\Helper\CreatePaymentMethod;
use Tests\Helper\PaymentCardFixture;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ListPaymentMethodsTest extends TestCase
{
    public function testMissingEffectiveAccountFailsClosed(): void
    {
        $fixture = new PaymentCardFixture();
        $fixture->monetizationAccountRepository->shouldReceive('findByAccountIdentifier')->once()->andReturnNull();
        $query = new ListPaymentMethods($fixture->monetizationAccountRepository, $fixture->principalRepository, $fixture->principalReachability, $fixture->policyEvaluator);
        $this->expectException(UnauthorizedException::class);
        $query->process(new ListPaymentMethodsInput($fixture->account->accountIdentifier(), $fixture->principalId));
    }

    #[Group('useDb')]
    public function testEmptyListAndRepeatedQueryRechecksAuthorization(): void
    {
        $fixture = new PaymentCardFixture();
        $fixture->allowAuthorization(Action::PAYMENT_METHOD_READ, times: 2, nextAllowed: false);
        $query = new ListPaymentMethods($fixture->monetizationAccountRepository, $fixture->principalRepository, $fixture->principalReachability, $fixture->policyEvaluator);
        $input = new ListPaymentMethodsInput($fixture->account->accountIdentifier(), $fixture->principalId);
        self::assertSame([], $query->process($input));
        $this->expectException(UnauthorizedException::class);
        $query->process($input);
    }

    #[Group('useDb')]
    public function testListsOnlyCurrentAccountCardsInStableIdentifierOrder(): void
    {
        $fixture = new PaymentCardFixture();
        CreateAccount::create((string) $fixture->account->accountIdentifier());
        CreateMonetizationAccount::create((string) $fixture->accountId, ['account_id' => (string) $fixture->account->accountIdentifier()]);
        $fixture->allowAuthorization(Action::PAYMENT_METHOD_READ);
        $first = '11111111-1111-4111-8111-111111111111';
        $second = '22222222-2222-4222-8222-222222222222';
        CreatePaymentMethod::create($second, ['monetization_account_id' => (string) $fixture->accountId,'brand' => 'mastercard','last4' => '5555']);
        CreatePaymentMethod::create($first, ['monetization_account_id' => (string) $fixture->accountId,'brand' => 'visa','last4' => '4242']);
        CreatePaymentMethod::create(StrTestHelper::generateUuid());
        $query = new ListPaymentMethods($fixture->monetizationAccountRepository, $fixture->principalRepository, $fixture->principalReachability, $fixture->policyEvaluator);
        $result = $query->process(new ListPaymentMethodsInput($fixture->account->accountIdentifier(), $fixture->principalId));
        self::assertSame([$first,$second], array_map(static fn ($row) => $row->toArray()['paymentMethodIdentifier'], $result));
        self::assertSame('4242', $result[0]->toArray()['last4']);
        self::assertArrayNotHasKey('externalPaymentMethodId', $result[0]->toArray());
    }
}
