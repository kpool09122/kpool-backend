<?php

declare(strict_types=1);

use Application\Http\Action\Webhook\Stripe\StripeWebhookAction;
use Illuminate\Support\Facades\Route;

Route::post('/stripe', StripeWebhookAction::class);
