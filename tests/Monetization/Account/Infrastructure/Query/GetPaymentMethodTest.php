<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Infrastructure\Query;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Account\Application\UseCase\Query\GetPaymentMethod\GetPaymentMethodInput;
use Source\Monetization\Account\Domain\Entity\MonetizationAccount;
use Source\Monetization\Account\Domain\Entity\PaymentMethodArchive;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodArchiveIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Account\Infrastructure\Query\GetPaymentMethod;
use Source\Monetization\Account\Infrastructure\Repository\PaymentMethodArchiveRepository;
use Source\Monetization\Account\Infrastructure\Repository\PaymentMethodRepository;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Tests\Helper\CreatePaymentMethod;
use Tests\Helper\PaymentCardFixture;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetPaymentMethodTest extends TestCase
{
    #[Group('useDb')]
    public function testReadsDeletionSnapshotWithTheUnchangedPaymentMethodIdentifier(): void
    {
        $id = new PaymentMethodIdentifier(StrTestHelper::generateUuid());
        CreatePaymentMethod::create((string) $id, ['brand' => 'visa','last4' => '4242']);
        $repository = new PaymentMethodRepository();
        $method = $repository->findById($id);
        self::assertNotNull($method);
        $archive = new PaymentMethodArchive(new PaymentMethodArchiveIdentifier(StrTestHelper::generateUuid()), $id, $method->monetizationAccountIdentifier(), $method->externalPaymentMethodId(), $method->type(), $method->meta()?->brand(), $method->meta()?->last4(), new DateTimeImmutable('2026-11-26T09:00:00Z'));
        (new PaymentMethodArchiveRepository())->save($archive);
        $repository->delete($id);
        $fixture = new PaymentCardFixture();
        $fixture->accountId = $method->monetizationAccountIdentifier();
        $fixture->account = new MonetizationAccount($fixture->accountId, $fixture->account->accountIdentifier(), [], null, null);
        $fixture->allowAuthorization(Action::PAYMENT_METHOD_READ);
        $query = new GetPaymentMethod($fixture->monetizationAccountRepository, $fixture->principalRepository, $fixture->principalReachability, $fixture->policyEvaluator);
        $result = $query->process(new GetPaymentMethodInput($fixture->account->accountIdentifier(), $id, $fixture->principalId));
        self::assertSame([
            'paymentMethodIdentifier' => (string) $id,
            'type' => 'card',
            'brand' => 'visa',
            'last4' => '4242',
            'archivedAt' => '2026-11-26T09:00:00+00:00',
        ], $result->toArray());
    }
}
