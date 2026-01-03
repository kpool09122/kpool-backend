<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Monetization\Account\Domain\Factory\MonetizationAccountFactoryInterface;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class MonetizationAccountFactoryTest extends TestCase
{
    /**
     * 正常系: 正しくMonetizationAccountを生成できること
     *
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $factory = $this->app->make(MonetizationAccountFactoryInterface::class);
        $account = $factory->create($accountIdentifier);

        $this->assertTrue(UuidValidator::isValid((string) $account->monetizationAccountIdentifier()));
        $this->assertSame($accountIdentifier, $account->accountIdentifier());
        $this->assertEmpty($account->capabilities());
        $this->assertNull($account->stripeCustomerId());
        $this->assertNull($account->stripeConnectedAccountId());
    }
}
