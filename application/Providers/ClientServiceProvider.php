<?php

declare(strict_types=1);

namespace Application\Providers;

use Application\Http\Client\OAuthHttpClient;
use Application\Http\Client\StripeClient;
use Illuminate\Support\ServiceProvider;

class ClientServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(StripeClient::class, function () {
            /** @var string $secretKey */
            $secretKey = config('services.stripe.secret_key', '');

            return new StripeClient($secretKey);
        });

        $this->app->singleton(OAuthHttpClient::class, function () {
            /** @var array<string, array<string, mixed>> $oauthConfig */
            $oauthConfig = config('oauth', []);

            return new OAuthHttpClient($oauthConfig);
        });
    }
}
