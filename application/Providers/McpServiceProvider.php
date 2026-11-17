<?php

declare(strict_types=1);

namespace Application\Providers;

use Application\Mcp\AccountApiAdapter;
use Application\Mcp\McpMetadata;
use Application\Mcp\McpTransport;
use Illuminate\Support\ServiceProvider;

class McpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/mcp_server.php', 'mcp_server');
        foreach ([AccountApiAdapter::class, McpMetadata::class, McpTransport::class] as $service) {
            $this->app->singleton($service);
        }
    }
}
