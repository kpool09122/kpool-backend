<?php

declare(strict_types=1);

namespace Tests\Http\Exceptions;

use Application\Http\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class HandlerTest extends TestCase
{
    public function testRenderJsonReturnsNotFoundForPrincipalNotFound(): void
    {
        Log::shouldReceive('warning')->once();

        $handler = new Handler();
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
        Log::shouldReceive('error')->once();

        $handler = new Handler();
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
        Log::shouldReceive('warning')->once();

        $validator = Validator::make([], [
            'resourceType' => ['required', 'string'],
        ]);

        $handler = new Handler();
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

    private function jsonRequest(): Request
    {
        return Request::create(
            '/api/wiki/drafts/01965bb2-bcc9-7c6f-8b90-89f7f217f002/approve',
            'POST',
            server: [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_ACCEPT_LANGUAGE' => 'ja',
            ],
        );
    }
}
