<?php

declare(strict_types=1);

namespace Tests\Helper;

use Application\Http\Client\Foundation\PsrFactories;
use Application\Http\Client\YouTubeClient\YouTubeClient;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Client\ClientInterface;
use Psr\Log\NullLogger;

class YouTubeClientTestHelper
{
    public static function create(ClientInterface $client, string $apiKey = 'test-key'): YouTubeClient
    {
        $factory = new HttpFactory();

        return new YouTubeClient(new Uri('https://www.googleapis.com/youtube/v3'), $apiKey, $client, new PsrFactories($factory, $factory, $factory), new NullLogger());
    }
}
