<?php

declare(strict_types=1);

namespace Tests\Http\Exceptions;

use Application\Http\Exceptions\Handler;
use Application\Http\Exceptions\ServiceUnavailableHttpException;
use Application\Http\Exceptions\TooManyRequestsHttpException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use RedisException;
use RuntimeException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class HandlerTest extends TestCase
{
    public function testRenderJsonReturnsNotFoundForPrincipalNotFound(): void
    {
        /** @var LoggerInterface&MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('warning')->once();

        $handler = new Handler($logger);
        $response = $handler(new PrincipalNotFoundException(), $this->jsonRequest());

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame([
            'status' => Response::HTTP_NOT_FOUND,
            'type' => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.5',
            'title' => 'Not Found',
            'detail' => '指定されたプリンシパルが見つかりません。',
        ], json_decode((string) $response->getContent(), true));
    }

    public function testRenderJsonLogsUnhandledServerException(): void
    {
        /** @var LoggerInterface&MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $handler = new Handler($logger);
        $response = $handler(new RuntimeException('boom'), $this->jsonRequest());

        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertSame([
            'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'title' => 'Internal Server Error',
            'detail' => 'サーバーエラーが発生しました。',
        ], json_decode((string) $response->getContent(), true));
    }

    public function testRenderJsonReturnsUnprocessableEntityForValidationException(): void
    {
        /** @var LoggerInterface&MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('warning')->once();

        $validator = Validator::make([], [
            'resourceType' => ['required', 'string'],
        ]);

        $handler = new Handler($logger);
        $response = $handler(new ValidationException($validator), $this->jsonRequest());

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertSame([
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'type' => 'https://datatracker.ietf.org/doc/html/rfc4918#section-11.2',
            'title' => 'Unprocessable Entity',
            'detail' => 'The resource type field is required.',
            'errors' => [
                'resourceType' => [
                    'The resource type field is required.',
                ],
            ],
        ], json_decode((string) $response->getContent(), true));
    }

    public function testRenderJsonPreservesRateLimitProblemDetailsAndRetryAfterHeader(): void
    {
        /** @var LoggerInterface&MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('warning')->once();

        $handler = new Handler($logger);
        $response = $handler(new TooManyRequestsHttpException(42), $this->jsonRequest());

        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
        $this->assertSame('42', $response->headers->get('Retry-After'));
        $this->assertSame([
            'status' => Response::HTTP_TOO_MANY_REQUESTS,
            'title' => 'Too Many Requests',
            'detail' => 'Too many requests. Please retry later.',
            'code' => 'rate_limit_exceeded',
        ], json_decode((string) $response->getContent(), true));
    }

    public function testRenderJsonPreservesServiceUnavailableProblemDetailsWithoutRetryAfterHeader(): void
    {
        /** @var LoggerInterface&MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $handler = new Handler($logger);
        $response = $handler(new ServiceUnavailableHttpException(), $this->jsonRequest());

        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $this->assertFalse($response->headers->has('Retry-After'));
        $this->assertSame([
            'status' => Response::HTTP_SERVICE_UNAVAILABLE,
            'title' => 'Service Unavailable',
            'detail' => 'The service is temporarily unavailable.',
            'code' => 'service_unavailable',
        ], json_decode((string) $response->getContent(), true));
    }

    public function testRenderJsonMapsRedisConnectionFailureToServiceUnavailable(): void
    {
        /** @var LoggerInterface&MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $handler = new Handler($logger);
        $response = $handler(new RedisException('Connection refused'), $this->jsonRequest());

        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($payload);
        $this->assertSame('service_unavailable', $payload['code']);
    }

    private function jsonRequest(): Request
    {
        return Request::create(
            '/api/v1/wiki/drafts/01965bb2-bcc9-7c6f-8b90-89f7f217f002/approve',
            'POST',
            server: [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_ACCEPT_LANGUAGE' => 'ja',
            ],
        );
    }
}
