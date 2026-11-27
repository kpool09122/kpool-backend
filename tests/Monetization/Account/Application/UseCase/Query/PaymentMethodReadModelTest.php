<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Application\UseCase\Query\PaymentMethodReadModel;

class PaymentMethodReadModelTest extends TestCase
{
    public function testSerializesCurrentPaymentMethod(): void
    {
        $row = new PaymentMethodReadModel('id', 'card', 'visa', '4242', 12, 2030, true, 'active');
        self::assertSame(['paymentMethodIdentifier' => 'id','type' => 'card','brand' => 'visa','last4' => '4242','expMonth' => 12,'expYear' => 2030,'isDefault' => true,'status' => 'active','archivedAt' => null], $row->toArray());
    }

    public function testNullDisplayInformationRemainsNull(): void
    {
        $row = new PaymentMethodReadModel('id', 'card', null, null, null, null, false, 'inactive');
        self::assertNull($row->toArray()['brand']);
        self::assertNull($row->toArray()['last4']);
        self::assertNull($row->toArray()['expMonth']);
        self::assertNull($row->toArray()['expYear']);
        self::assertNull($row->toArray()['archivedAt']);
    }
}
