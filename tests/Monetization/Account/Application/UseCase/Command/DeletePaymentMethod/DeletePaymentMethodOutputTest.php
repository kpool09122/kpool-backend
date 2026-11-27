<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\UseCase\Command\DeletePaymentMethod;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Application\UseCase\Command\DeletePaymentMethod\DeletePaymentMethodOutput;

class DeletePaymentMethodOutputTest extends TestCase
{
    public function testEmptyOutput(): void
    {
        self::assertSame([], (new DeletePaymentMethodOutput())->toArray());
    }
}
