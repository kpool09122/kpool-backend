<?php

declare(strict_types=1);

namespace Application\Providers;

use Application\Http\Client\Foundation\PsrFactories;
use Application\Http\Client\GeminiClient\GeminiClient;
use Application\Http\Client\GoogleTranslateClient\GoogleTranslateClient;
use Application\Http\Client\OAuthHttpClient\OAuthHttpClient;
use Application\Http\Client\StripeClient\StripeClient;
use Application\Http\Client\YouTubeClient\YouTubeClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Client\ClientInterface;

class ClientServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(ClientInterface::class, function () {
            return new GuzzleClient();
        });

        $this->app->singleton(PsrFactories::class, function () {
            $httpFactory = new HttpFactory();

            return new PsrFactories(
                $httpFactory,
                $httpFactory,
                $httpFactory,
            );
        });

        $this->app->singleton(StripeClient::class, function () {
            /** @var string $secretKey */
            $secretKey = config('services.stripe.secret_key', '');

            return new StripeClient($secretKey);
        });

        $this->app->singleton(OAuthHttpClient::class, function (Application $app) {
            /** @var array<string, array<string, mixed>> $oauthConfig */
            $oauthConfig = config('oauth', []);

            $httpFactory = new HttpFactory();

            return new OAuthHttpClient(
                uri: $httpFactory->createUri('https://oauth.example.com'),
                client: $app->make(ClientInterface::class),
                psrFactories: $app->make(PsrFactories::class),
                config: $oauthConfig,
            );
        });

        $this->app->singleton(YouTubeClient::class, function (Application $app) {
            /** @var string $apiKey */
            $apiKey = config('youtube.api_key', '');

            $httpFactory = new HttpFactory();

            return new YouTubeClient(
                uri: $httpFactory->createUri('https://www.googleapis.com'),
                apiKey: $apiKey,
                client: $app->make(ClientInterface::class),
                psrFactories: $app->make(PsrFactories::class),
            );
        });

        $this->app->singleton(GoogleTranslateClient::class, function () {
            /** @var string $projectId */
            $projectId = config('google.project_id', '');

            /** @var string $credentialsPath */
            $credentialsPath = config('google.credentials_path', '');

            return new GoogleTranslateClient($projectId, $credentialsPath);
        });

        $this->app->singleton(GeminiClient::class, function (Application $app) {
            /** @var string $apiKey */
            $apiKey = config('google.gemini_api_key', '');

            /** @var string $model */
            $model = config('google.gemini_model', 'gemini-2.0-flash');

            return new GeminiClient(
                apiKey: $apiKey,
                model: $model,
                httpClient: $app->make(ClientInterface::class),
                psrFactories: $app->make(PsrFactories::class),
            );
        });
    }
}
