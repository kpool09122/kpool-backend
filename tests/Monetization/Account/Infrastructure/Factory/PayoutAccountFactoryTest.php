<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Monetization\Account\Domain\Factory\PayoutAccountFactoryInterface;
use Source\Monetization\Account\Domain\ValueObject\ExternalAccountId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PayoutAccountStatus;
use Source\Monetization\Account\Infrastructure\Factory\PayoutAccountFactory;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PayoutAccountFactoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(PayoutAccountFactoryInterface::class);
        $this->assertInstanceOf(PayoutAccountFactory::class, $factory);
    }

    /**
     * 正常系: 正しくPayoutAccountエンティティが作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $monetizationAccountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $externalAccountId = new ExternalAccountId('ba_' . StrTestHelper::generateStr(10));

        $factory = $this->app->make(PayoutAccountFactoryInterface::class);
        $payoutAccount = $factory->create(
            $monetizationAccountIdentifier,
            $externalAccountId,
        );

        $this->assertTrue(UuidValidator::isValid((string) $payoutAccount->payoutAccountIdentifier()));
        $this->assertSame($monetizationAccountIdentifier, $payoutAccount->monetizationAccountIdentifier());
        $this->assertSame($externalAccountId, $payoutAccount->externalAccountId());
        $this->assertNull($payoutAccount->meta());
        $this->assertFalse($payoutAccount->isDefault());
        $this->assertSame(PayoutAccountStatus::ACTIVE, $payoutAccount->status());
    }
}
