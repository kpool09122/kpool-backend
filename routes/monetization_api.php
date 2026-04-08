<?php

declare(strict_types=1);

use Application\Http\Action\Monetization\Account\Command\OnboardSeller\OnboardSellerAction;
use Application\Http\Action\Monetization\Account\Command\ProvisionMonetizationAccount\ProvisionMonetizationAccountAction;
use Application\Http\Action\Monetization\Account\Command\RegisterPaymentMethod\RegisterPaymentMethodAction;
use Application\Http\Action\Monetization\Account\Command\SyncPayoutAccount\SyncPayoutAccountAction;
use Application\Http\Action\Monetization\Billing\Command\CreateInvoice\CreateInvoiceAction;
use Application\Http\Action\Monetization\Billing\Command\RecordPayment\RecordPaymentAction;
use Application\Http\Action\Monetization\Payment\Command\AuthorizePayment\AuthorizePaymentAction;
use Application\Http\Action\Monetization\Payment\Command\CapturePayment\CapturePaymentAction;
use Application\Http\Action\Monetization\Payment\Command\RefundPayment\RefundPaymentAction;
use Application\Http\Action\Monetization\Settlement\Command\ExecuteTransfer\ExecuteTransferAction;
use Application\Http\Action\Monetization\Settlement\Command\SettleRevenue\SettleRevenueAction;
use Illuminate\Support\Facades\Route;

// Account
Route::post('/accounts', ProvisionMonetizationAccountAction::class);
Route::post('/accounts/{monetizationAccountId}/onboard-seller', OnboardSellerAction::class);
Route::post('/accounts/{monetizationAccountId}/register-payment-method', RegisterPaymentMethodAction::class);
Route::post('/accounts/sync-payout-account', SyncPayoutAccountAction::class);

// Payment
Route::post('/payments/authorize', AuthorizePaymentAction::class);
Route::post('/payments/{paymentId}/capture', CapturePaymentAction::class);
Route::post('/payments/{paymentId}/refund', RefundPaymentAction::class);

// Billing
Route::post('/invoices', CreateInvoiceAction::class);
Route::post('/invoices/{invoiceId}/record-payment', RecordPaymentAction::class);

// Settlement
Route::post('/transfers/{transferId}/execute', ExecuteTransferAction::class);
Route::post('/settlements/settle-revenue', SettleRevenueAction::class);
