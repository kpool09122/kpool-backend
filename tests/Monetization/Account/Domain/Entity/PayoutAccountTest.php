<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\Entity\PayoutAccount;
use Source\Monetization\Account\Domain\ValueObject\AccountHolderType;
use Source\Monetization\Account\Domain\ValueObject\ExternalAccountId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PayoutAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PayoutAccountMeta;
use Source\Monetization\Account\Domain\ValueObject\PayoutAccountStatus;
use Tests\Helper\StrTestHelper;

class PayoutAccountTest extends TestCase
{
    private function createPayoutAccount(
        ?PayoutAccountMeta $meta = null,
        bool $isDefault = false,
        PayoutAccountStatus $status = PayoutAccountStatus::ACTIVE,
    ): PayoutAccount {
        return new PayoutAccount(
            new PayoutAccountIdentifier(StrTestHelper::generateUuid()),
            new MonetizationAccountIdentifier(StrTestHelper::generateUuid()),
            new ExternalAccountId('ba_1234567890abcdef'),
            $meta,
            $isDefault,
            $status,
        );
    }

    /**
     * 正常系: 全フィールドを指定してインスタンスが生成されること
     */
    public function test__construct(): void
    {
        $payoutAccountIdentifier = new PayoutAccountIdentifier(StrTestHelper::generateUuid());
        $monetizationAccountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $externalAccountId = new ExternalAccountId('ba_1234567890abcdef');
        $meta = new PayoutAccountMeta('MUFG', '1234', 'JP', 'jpy', AccountHolderType::INDIVIDUAL);

        $payoutAccount = new PayoutAccount(
            $payoutAccountIdentifier,
            $monetizationAccountIdentifier,
            $externalAccountId,
            $meta,
            true,
            PayoutAccountStatus::ACTIVE,
        );

        $this->assertSame($payoutAccountIdentifier, $payoutAccount->payoutAccountIdentifier());
        $this->assertSame($monetizationAccountIdentifier, $payoutAccount->monetizationAccountIdentifier());
        $this->assertSame($externalAccountId, $payoutAccount->externalAccountId());
        $this->assertSame($meta, $payoutAccount->meta());
        $this->assertTrue($payoutAccount->isDefault());
        $this->assertSame(PayoutAccountStatus::ACTIVE, $payoutAccount->status());
    }

    /**
     * 正常系: デフォルト引数でインスタンスが生成されること
     */
    public function test__constructWithDefaults(): void
    {
        $payoutAccount = $this->createPayoutAccount();

        $this->assertNull($payoutAccount->meta());
        $this->assertFalse($payoutAccount->isDefault());
        $this->assertSame(PayoutAccountStatus::ACTIVE, $payoutAccount->status());
    }

    /**
     * 正常系: updateMetaでメタ情報が更新されること
     */
    public function testUpdateMeta(): void
    {
        $payoutAccount = $this->createPayoutAccount();

        $this->assertNull($payoutAccount->meta());

        $meta = new PayoutAccountMeta('MUFG', '1234', 'JP', 'jpy', AccountHolderType::INDIVIDUAL);
        $payoutAccount->updateMeta($meta);

        $this->assertSame($meta, $payoutAccount->meta());
    }

    /**
     * 正常系: markAsDefaultでデフォルトに設定されること
     */
    public function testMarkAsDefault(): void
    {
        $payoutAccount = $this->createPayoutAccount();

        $this->assertFalse($payoutAccount->isDefault());

        $payoutAccount->markAsDefault();

        $this->assertTrue($payoutAccount->isDefault());
    }

    /**
     * 正常系: unmarkAsDefaultでデフォルトが解除されること
     */
    public function testUnmarkAsDefault(): void
    {
        $payoutAccount = $this->createPayoutAccount(isDefault: true);

        $this->assertTrue($payoutAccount->isDefault());

        $payoutAccount->unmarkAsDefault();

        $this->assertFalse($payoutAccount->isDefault());
    }

    /**
     * 正常系: deactivateでステータスがINACTIVEに変更されること
     */
    public function testDeactivate(): void
    {
        $payoutAccount = $this->createPayoutAccount();

        $this->assertSame(PayoutAccountStatus::ACTIVE, $payoutAccount->status());

        $payoutAccount->deactivate();

        $this->assertSame(PayoutAccountStatus::INACTIVE, $payoutAccount->status());
    }

    /**
     * 正常系: activateでステータスがACTIVEに変更されること
     */
    public function testActivate(): void
    {
        $payoutAccount = $this->createPayoutAccount(status: PayoutAccountStatus::INACTIVE);

        $this->assertSame(PayoutAccountStatus::INACTIVE, $payoutAccount->status());

        $payoutAccount->activate();

        $this->assertSame(PayoutAccountStatus::ACTIVE, $payoutAccount->status());
    }
}
