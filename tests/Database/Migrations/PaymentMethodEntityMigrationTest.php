<?php

declare(strict_types=1);

namespace Tests\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Monetization\Account\Domain\Repository\PaymentMethodRepositoryInterface;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Tests\Helper\CreateMonetizationAccount;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class PaymentMethodEntityMigrationTest extends TestCase
{
    public function testUpdatesLegacySchemaAndCanRollBack(): void
    {
        $this->legacySchema();
        /** @var Migration $migration */
        $migration = require __DIR__ . '/../../../database/migrations/2026_11_25_000001_consolidate_payment_methods.php';
        if (! method_exists($migration, 'up') || ! method_exists($migration, 'down')) {
            throw new RuntimeException('Migration must have up and down methods.');
        }
        $migration->up();

        $this->assertFalse(Schema::hasTable('monetization_registered_payment_methods'));
        $this->assertTrue(Schema::hasColumns('monetization_payment_methods', [
            'external_payment_method_id',
        ]));
        foreach (['payment_method_type', 'payment_method_label', 'payment_method_recurring_enabled', 'stripe_payment_method_id'] as $column) {
            $this->assertFalse(Schema::hasColumn('payments', $column));
        }
        $this->assertTrue(Schema::hasColumn('payments', 'payment_method_id'));
        $this->assertTrue(Schema::hasIndex('monetization_payment_methods', ['monetization_account_id', 'external_payment_method_id'], 'unique'));
        $this->assertFalse(Schema::hasIndex('monetization_payment_methods', ['external_payment_method_id'], 'unique'));

        $migration->down();

        $this->assertFalse(Schema::hasTable('monetization_payment_methods'));
        $this->assertTrue(Schema::hasColumn('monetization_registered_payment_methods', 'stripe_payment_method_id'));
        $this->assertTrue(Schema::hasIndex('monetization_registered_payment_methods', ['stripe_payment_method_id'], 'unique'));
        $this->assertFalse(Schema::hasColumn('monetization_registered_payment_methods', 'label'));
        $this->assertFalse(Schema::hasColumn('monetization_registered_payment_methods', 'recurring_enabled'));
        $this->assertTrue(Schema::hasColumns('payments', [
            'payment_method_type', 'payment_method_label', 'payment_method_recurring_enabled', 'stripe_payment_method_id',
        ]));
    }

    public function testPreservesRegisteredCardMetadataThroughSchemaChanges(): void
    {
        $this->legacySchema();
        $accountId = StrTestHelper::generateUuid();
        CreateMonetizationAccount::create($accountId);
        $methodId = StrTestHelper::generateUuid();
        DB::table('monetization_registered_payment_methods')->insert([
            'id' => $methodId,
            'monetization_account_id' => $accountId,
            'stripe_payment_method_id' => 'pm_1234567890',
            'type' => 'card',
            'brand' => 'visa',
            'last4' => '4242',
            'is_default' => true,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /** @var Migration $migration */
        $migration = require __DIR__ . '/../../../database/migrations/2026_11_25_000001_consolidate_payment_methods.php';
        if (! method_exists($migration, 'up') || ! method_exists($migration, 'down')) {
            throw new RuntimeException('Migration must have up and down methods.');
        }
        $migration->up();

        $this->assertFalse(Schema::hasColumn('monetization_payment_methods', 'stripe_payment_method_id'));
        $this->assertDatabaseHas('monetization_payment_methods', ['id' => $methodId, 'external_payment_method_id' => 'pm_1234567890']);
        $method = $this->app()->make(PaymentMethodRepositoryInterface::class)->findById(new PaymentMethodIdentifier($methodId));
        $this->assertNotNull($method);
        $this->assertSame('pm_1234567890', (string) $method->externalPaymentMethodId());
        $this->assertNotNull($method->meta());
        $this->assertSame('visa', $method->meta()->brand());
        $this->assertSame('4242', $method->meta()->last4());
        $this->assertTrue($method->isDefault());

        $migration->down();

        $this->assertFalse(Schema::hasTable('monetization_payment_methods'));
        $this->assertDatabaseHas('monetization_registered_payment_methods', ['id' => $methodId, 'stripe_payment_method_id' => 'pm_1234567890', 'brand' => 'visa', 'last4' => '4242']);
    }

    private function legacySchema(): void
    {
        Schema::drop('monetization_payment_methods');
        Schema::drop('payments');

        foreach ([
            '2024_01_01_000016_create_monetization_payment_methods_table.php',
            '2024_01_01_000020_create_payments_table.php',
        ] as $filename) {
            /** @var Migration $migration */
            $migration = require __DIR__ . '/../../../database/migrations/' . $filename;
            if (! method_exists($migration, 'up')) {
                throw new RuntimeException('Migration must have an up method.');
            }
            $migration->up();
        }
    }
}
