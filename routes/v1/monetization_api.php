<?php

declare(strict_types=1);

use Application\Http\Action\Monetization\Principal\Command\CreatePrincipal\CreatePrincipalAction;
use Application\Http\Action\Monetization\Principal\Query\GetCurrentPrincipal\GetCurrentPrincipalAction;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.api', 'account.active', 'principal.active', 'resolve.actor', 'resolve.account', 'rate-limit:screen,command'])->group(function () {
    Route::post('/principals', CreatePrincipalAction::class);
});
Route::middleware(['auth.api', 'resolve.actor', 'resolve.account', 'rate-limit:screen,query'])->group(function () {
    Route::get('/principal/me', GetCurrentPrincipalAction::class);
});

// Account
// Disabled by #605: Route::post('/accounts', ProvisionMonetizationAccountAction::class);
// Disabled by #605: Route::post('/accounts/{monetizationAccountId}/onboard-seller', OnboardSellerAction::class);
// Disabled by #605: Route::post('/accounts/{monetizationAccountId}/register-payment-method', RegisterPaymentMethodAction::class);
// Disabled by #605: Route::post('/accounts/sync-payout-account', SyncPayoutAccountAction::class);

// Payment
// Disabled by #605: Route::post('/payments/authorize', AuthorizePaymentAction::class);
// Disabled by #605: Route::post('/payments/{paymentId}/capture', CapturePaymentAction::class);
// Disabled by #605: Route::post('/payments/{paymentId}/refund', RefundPaymentAction::class);

// Billing
// Disabled by #605: Route::post('/invoices', CreateInvoiceAction::class);
// Disabled by #605: Route::post('/invoices/{invoiceId}/record-payment', RecordPaymentAction::class);

// Settlement
// Disabled by #605: Route::post('/transfers/{transferId}/execute', ExecuteTransferAction::class);
// Disabled by #605: Route::post('/settlements/settle-revenue', SettleRevenueAction::class);
