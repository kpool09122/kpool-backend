<?php

declare(strict_types=1);

namespace Application\Http\Client;

use Stripe\StripeClient as BaseStripeClient;

class StripeClient
{
    private ?BaseStripeClient $client = null;

    public function __construct(
        private readonly string $secretKey,
        private readonly string $apiVersion = '2024-12-18.acacia',
    ) {
    }

    public function client(): BaseStripeClient
    {
        if ($this->client === null) {
            $this->client = new BaseStripeClient([
                'api_key' => $this->secretKey,
                'stripe_version' => $this->apiVersion,
            ]);
        }

        return $this->client;
    }
}
