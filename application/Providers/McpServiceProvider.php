<?php

declare(strict_types=1);

namespace Application\Providers;

use Application\Mcp\AccountResultProjector;
use Application\Mcp\McpApiClient;
use Application\Mcp\McpMetadata;
use Application\Mcp\McpToolExecutor;
use Application\Mcp\McpTransport;
use Application\Mcp\OpenApiToolRegistry;
use Illuminate\Support\ServiceProvider;

class McpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/mcp_server.php', 'mcp_server');
        $this->mergeConfigFrom(__DIR__ . '/../../config/mcp_tools.php', 'mcp_tools');
        $this->app->singleton(OpenApiToolRegistry::class, fn () => new OpenApiToolRegistry(config()->array('mcp_tools')));
        foreach ([McpToolExecutor::class, AccountResultProjector::class, McpApiClient::class, McpMetadata::class, McpTransport::class] as $service) {
            $this->app->singleton($service);
        }
    }
}
