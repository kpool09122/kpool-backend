<?php

declare(strict_types=1);

namespace Application\Mcp;

use SensitiveParameter;
use Source\Identity\Application\Exception\InvalidAuthorizationTokenException;
use Source\Identity\Application\Exception\InvalidInternalApiTokenConfigurationException;
use stdClass;

final readonly class McpToolExecutor
{
    public function __construct(private McpApiClient $mcpApiClient, private AccountResultProjector $accountResultProjector)
    {
    }

    /** @return array<string, mixed>
     * @throws InvalidAuthorizationTokenException
     * @throws InvalidInternalApiTokenConfigurationException
     */
    public function execute(OpenApiTool $tool, stdClass $arguments, #[SensitiveParameter] string $bearer): array
    {
        $request = $tool->request($arguments);
        $data = $this->mcpApiClient->request($bearer, $tool->method, $request['path'], $request['body'], $request['query']);

        return match ($tool->resultProjector) {
            'account_context' => $this->accountResultProjector->context($data),
            'account_switched' => ['switched' => true],
            default => $data,
        };
    }
}
