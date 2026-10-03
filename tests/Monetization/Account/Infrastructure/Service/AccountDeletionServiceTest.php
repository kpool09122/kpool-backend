<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Infrastructure\Service;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Account\Application\Service\AccountDeletionServiceInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class AccountDeletionServiceTest extends TestCase
{
    public function testDeletesInvoicesAndPaymentsBeforeMonetizationAccountCascade(): void
    {
        $account = StrTestHelper::generateUuid();
        $monetizationAccount = StrTestHelper::generateUuid();
        $invoice = StrTestHelper::generateUuid();
        $payment = StrTestHelper::generateUuid();
        CreateAccount::create($account);
        DB::table('monetization_accounts')->insert(['id' => $monetizationAccount, 'account_id' => $account]);
        DB::table('invoices')->insert([
            'id' => $invoice, 'order_id' => StrTestHelper::generateUuid(), 'buyer_monetization_account_id' => $monetizationAccount,
            'currency' => 'JPY', 'subtotal' => 100, 'discount_amount' => 0, 'tax_amount' => 0, 'total' => 100,
            'issued_at' => now(), 'due_date' => now(), 'status' => 'paid', 'tax_document_reason' => 'Private billing data',
        ]);
        DB::table('invoice_lines')->insert(['invoice_id' => $invoice, 'description' => 'Private purchase', 'currency' => 'JPY', 'unit_price' => 100, 'quantity' => 1]);
        DB::table('payments')->insert([
            'id' => $payment, 'order_id' => StrTestHelper::generateUuid(), 'buyer_monetization_account_id' => $monetizationAccount,
            'currency' => 'JPY', 'amount' => 100, 'payment_method_id' => StrTestHelper::generateUuid(), 'payment_method_type' => 'card',
            'payment_method_label' => 'Private card label', 'payment_method_recurring_enabled' => false, 'created_at' => now(), 'status' => 'captured',
        ]);

        $this->app()->make(AccountDeletionServiceInterface::class)->delete(new AccountIdentifier($account));

        $this->assertDatabaseMissing('invoices', ['id' => $invoice]);
        $this->assertDatabaseMissing('invoice_lines', ['invoice_id' => $invoice]);
        $this->assertDatabaseMissing('payments', ['id' => $payment]);
        $this->assertDatabaseHas('accounts', ['id' => $account]);
    }
}
