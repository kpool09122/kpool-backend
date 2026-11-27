<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Application\UseCase\Query\ArchivedPaymentMethodReadModel;

class ArchivedPaymentMethodReadModelTest extends TestCase
{
    public function testSerializesHistoryDisplayInformation(): void
    {
        $row = new ArchivedPaymentMethodReadModel('id', 'card', 'visa', '4242', '2026-11-26T09:00:00+00:00');
        self::assertSame([
            'paymentMethodIdentifier' => 'id',
            'type' => 'card',
            'brand' => 'visa',
            'last4' => '4242',
            'archivedAt' => '2026-11-26T09:00:00+00:00',
        ], $row->toArray());
    }

    public function testPreservesUnavailableCardDisplayInformationAsNull(): void
    {
        $row = new ArchivedPaymentMethodReadModel('id', 'card', null, null, '2026-11-26T09:00:00+00:00');
        self::assertNull($row->toArray()['brand']);
        self::assertNull($row->toArray()['last4']);
    }
}
