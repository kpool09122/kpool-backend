<?php

declare(strict_types=1);

namespace Application\Http\Action\Mcp;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use League\OAuth2\Server\Exception\OAuthServerException;
use Nyholm\Psr7\ServerRequest;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SensitiveParameter;
use Source\Identity\Application\Service\AuthorizationConnectionServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ExchangeTokenAction
{
    public function __construct(private AuthorizationConnectionServiceInterface $authorizationConnectionService, private LoggerInterface $logger)
    {
    }

    public function __invoke(#[SensitiveParameter] Request $request): Response
    {
        $headers = ['Cache-Control' => 'no-store', 'Pragma' => 'no-cache'];
        if (! str_starts_with(strtolower($request->header('Content-Type', '')), 'application/x-www-form-urlencoded') || strlen($request->getContent()) > 16384) {
            return new JsonResponse(['error' => 'invalid_request'], 400, $headers);
        }
        $psr = (new ServerRequest('POST', config()->string('mcp_oauth.issuer') . '/oauth/token', $request->headers->all()))
            ->withParsedBody($request->request->all());

        try {
            $response = DB::transaction(fn () => $this->authorizationConnectionService->exchange($psr));
        } catch (OAuthServerException $exception) {
            return new JsonResponse(['error' => $exception->getErrorType()], $exception->getHttpStatusCode(), $headers + $exception->getHttpHeaders());
        } catch (Throwable $e) {
            // Token/provider exceptions may carry credentials; log only a sanitized error.
            $e = new RuntimeException('MCP token exchange failed.');
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: 'Internal server error.', previous: $e);
        }

        return new Response((string) $response->getBody(), $response->getStatusCode(), $headers + ['Content-Type' => 'application/json']);
    }
}
