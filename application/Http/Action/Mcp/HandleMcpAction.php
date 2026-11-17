<?php

declare(strict_types=1);

namespace Application\Http\Action\Mcp;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Mcp\McpTransport;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SensitiveParameter;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class HandleMcpAction
{
    public function __construct(private McpTransport $mcpTransport, private LoggerInterface $logger)
    {
    }

    public function __invoke(#[SensitiveParameter] Request $request): Response
    {
        try {
            return $this->mcpTransport->handle($request);
        } catch (Throwable $e) {
            // Transport exceptions can contain credentials. Never retain their message/chain.
            $e = new RuntimeException('MCP request failed.');
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: 'Internal server error.', previous: $e);
        }
    }
}
