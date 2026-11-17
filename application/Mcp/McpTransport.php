<?php

declare(strict_types=1);

namespace Application\Mcp;

use Illuminate\Http\Request;
use JsonException;
use SensitiveParameter;
use Source\Identity\Application\Exception\InvalidAuthorizationTokenException;
use Source\Identity\Application\Exception\InvalidInternalApiTokenConfigurationException;
use Source\Identity\Application\Service\AuthorizationTokenVerifierInterface;
use Source\Identity\Application\Service\ValidatedAuthorizationToken;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

readonly class McpTransport
{
    public function __construct(
        private AuthorizationTokenVerifierInterface $authorizationTokenVerifier,
        private OpenApiToolRegistry $openApiToolRegistry,
        private McpToolExecutor $mcpToolExecutor,
        private McpToolVisibility $mcpToolVisibility,
    ) {
    }

    public function handle(Request $request): Response
    {
        if (! config()->boolean('mcp_server.enabled')) {
            return new JsonResponse(['error' => 'not_found'], 404);
        }
        $headers = $request->headers->all('Authorization');
        if (count($headers) !== 1 || preg_match('/\ABearer ([A-Za-z0-9._~+\/-]+=*)\z/iD', $headers[0] ?? '', $matches) !== 1) {
            return $this->challenge(401, 'invalid_token');
        }

        try {
            $validated = $this->authorizationTokenVerifier->verify($matches[1]);
        } catch (InvalidAuthorizationTokenException) {
            return $this->challenge(401, 'invalid_token');
        }

        $origin = $request->headers->get('Origin');
        if ($origin !== null && ! in_array($origin, config()->array('mcp_server.allowed_origins'), true)) {
            return new JsonResponse(['error' => 'invalid_origin'], 403);
        }
        if ($request->method() !== 'POST') {
            return new Response('', 405, ['Allow' => 'POST']);
        }
        $version = $request->header('MCP-Protocol-Version');
        if ($version !== null && ! in_array($version, ['2025-03-26', '2025-06-18', '2025-11-25'], true)) {
            return new JsonResponse(['error' => 'unsupported_protocol_version'], 400);
        }
        $accept = $request->header('Accept', '');
        if (! str_contains($accept, 'application/json') || ! str_contains($accept, 'text/event-stream')) {
            return new JsonResponse(['error' => 'not_acceptable'], 406);
        }
        if (! str_starts_with(strtolower($request->header('Content-Type', '')), 'application/json')) {
            return new JsonResponse(['error' => 'unsupported_media_type'], 415);
        }
        if (strlen($request->getContent()) > 65536) {
            return new JsonResponse(['error' => 'request_too_large'], 413);
        }

        try {
            $message = json_decode($request->getContent(), false, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->rpcError(null, -32700, 'Parse error', 400);
        }
        if (! $message instanceof stdClass || ($message->jsonrpc ?? null) !== '2.0'
            || ! is_string($message->method ?? null)
            || (property_exists($message, 'id') && ! is_int($message->id) && ! is_string($message->id))
            || (property_exists($message, 'params') && ! $message->params instanceof stdClass)) {
            return $this->rpcError(null, -32600, 'Invalid Request', 400);
        }
        // Notifications must never execute tools or any other operation with side effects.
        if (! property_exists($message, 'id')) {
            return new Response('', 202);
        }
        $id = $message->id;
        $params = $message->params ?? new stdClass();
        if ((! is_int($id) && ! is_string($id)) || ! $params instanceof stdClass) {
            return $this->rpcError(null, -32600, 'Invalid Request', 400);
        }
        switch ($message->method) {
            case 'initialize':
                if (! is_string($params->protocolVersion ?? null) || ! ($params->capabilities ?? null) instanceof stdClass
                    || ! ($params->clientInfo ?? null) instanceof stdClass
                    || ! is_string($params->clientInfo->name ?? null) || ! is_string($params->clientInfo->version ?? null)) {
                    return $this->rpcError($id, -32602, 'Invalid params');
                }
                $protocol = in_array($params->protocolVersion, ['2025-03-26', '2025-06-18', '2025-11-25'], true) ? $params->protocolVersion : '2025-11-25';

                return $this->rpcResult($id, ['protocolVersion' => $protocol, 'capabilities' => ['tools' => ['listChanged' => false]], 'serverInfo' => ['name' => 'k-pool', 'version' => '1.0.0']]);
            case 'ping':
                return $this->rpcResult($id, new stdClass());
            case 'tools/list':
                return $this->rpcResult($id, ['tools' => $this->mcpToolVisibility->filter($this->openApiToolRegistry->definitions(), $validated)]);
            case 'tools/call':
                return $this->callTool($id, $params, $matches[1], $validated);
            default:
                return $this->rpcError($id, -32601, 'Method not found');
        }
    }

    private function callTool(int|string $id, stdClass $params, #[SensitiveParameter] string $bearer, ValidatedAuthorizationToken $validated): Response
    {
        $name = $params->name ?? null;
        if (! is_string($name) || ($tool = $this->openApiToolRegistry->find($name)) === null || array_diff(array_keys(get_object_vars($params)), ['name', 'arguments', '_meta']) !== []) {
            return $this->rpcError($id, -32602, 'Invalid params');
        }
        $arguments = $params->arguments ?? new stdClass();
        if (! $arguments instanceof stdClass) {
            return $this->rpcError($id, -32602, 'Invalid params');
        }
        if (! $tool->accepts($arguments)) {
            return $this->rpcError($id, -32602, 'Invalid params');
        }
        $granted = array_map(static fn (AuthorizationScope $scope): string => $scope->value, $validated->scopes);
        $required = $tool->scopes;
        if (array_diff($required, $granted) !== []) {
            return $this->challenge(403, 'insufficient_scope');
        }

        try {
            $data = $this->mcpToolExecutor->execute($tool, $arguments, $bearer);
        } catch (InvalidAuthorizationTokenException) {
            return $this->challenge(401, 'invalid_token');
        } catch (InvalidInternalApiTokenConfigurationException) {
            return new JsonResponse(['error' => 'temporarily_unavailable'], 503);
        } catch (ApiFailure $exception) {
            if ($exception->status === 401 || $exception->errorCode === 'insufficient_scope') {
                return $this->challenge($exception->status, $exception->status === 401 ? 'invalid_token' : 'insufficient_scope');
            }

            return $this->rpcResult($id, [
                'isError' => true,
                'content' => [['type' => 'text', 'text' => $exception->errorCode]],
                'structuredContent' => ['status' => $exception->status, 'code' => $exception->errorCode, 'retryAfter' => $exception->retryAfter],
            ]);
        }

        return $this->rpcResult($id, ['content' => [['type' => 'text', 'text' => json_encode($data, JSON_THROW_ON_ERROR)]], 'structuredContent' => $data, 'isError' => false]);
    }

    /** @param array<string, mixed>|stdClass $result */
    private function rpcResult(int|string $id, array|stdClass $result): Response
    {
        return new JsonResponse(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result], 200, ['Cache-Control' => 'no-store']);
    }

    private function rpcError(int|string|null $id, int $code, string $message, int $status = 200): Response
    {
        return new JsonResponse(['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]], $status, ['Cache-Control' => 'no-store']);
    }

    private function challenge(int $status, string $error): Response
    {
        return new JsonResponse(['error' => $error], $status, [
            'Cache-Control' => 'no-store',
            'WWW-Authenticate' => 'Bearer resource_metadata="' . rtrim(config()->string('mcp_oauth.issuer'), '/') . '/.well-known/oauth-protected-resource/mcp", error="' . $error . '"',
        ]);
    }
}
