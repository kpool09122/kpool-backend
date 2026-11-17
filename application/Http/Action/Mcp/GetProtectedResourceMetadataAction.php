<?php

declare(strict_types=1);

namespace Application\Http\Action\Mcp;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Mcp\McpMetadata;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

readonly class GetProtectedResourceMetadataAction
{
    public function __construct(private McpMetadata $mcpMetadata, private LoggerInterface $logger)
    {
    }

    public function __invoke(): JsonResponse
    {
        try {
            return response()->json($this->mcpMetadata->resource());
        } catch (Throwable $e) {
            // Transport exceptions can contain credentials. Never retain their message/chain.
            $e = new RuntimeException('MCP request failed.');
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: 'Internal server error.', previous: $e);
        }
    }
}
