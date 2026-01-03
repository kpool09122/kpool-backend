<?php

declare(strict_types=1);

namespace Application\Providers;

use Application\Http\Client\StripeClient;
use Illuminate\Support\ServiceProvider;

class ClientServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(StripeClient::class, fn () => new StripeClient(
            (string) config('services.stripe.secret_key', ''),
        ));
    }
}
