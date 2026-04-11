<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Http\Action\Command\DetachRoleFromPrincipalGroup;

use Application\Http\Action\Wiki\Principal\Command\DetachRoleFromPrincipalGroup\DetachRoleFromPrincipalGroupAction;
use Application\Http\Action\Wiki\Principal\Command\DetachRoleFromPrincipalGroup\DetachRoleFromPrincipalGroupRequest;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\DetachRoleFromPrincipalGroup\DetachRoleFromPrincipalGroupInput;
use Source\Wiki\Principal\Application\UseCase\Command\DetachRoleFromPrincipalGroup\DetachRoleFromPrincipalGroupInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DetachRoleFromPrincipalGroupActionTest extends TestCase
{
    public function testInvokeReturnsNoContentResponse(): void
    {
        /** @var DetachRoleFromPrincipalGroupRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(DetachRoleFromPrincipalGroupRequest::class);
        $request->shouldReceive('principalGroupId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('roleIdentifier')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var DetachRoleFromPrincipalGroupInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(DetachRoleFromPrincipalGroupInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(Mockery::type(DetachRoleFromPrincipalGroupInput::class));

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $action = new DetachRoleFromPrincipalGroupAction($useCase, $logger);

        $response = $action($request);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    public function testInvokeReturnsNotFoundResponseWhenPrincipalGroupNotFound(): void
    {
        /** @var DetachRoleFromPrincipalGroupRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(DetachRoleFromPrincipalGroupRequest::class);
        $request->shouldReceive('principalGroupId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('roleIdentifier')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var DetachRoleFromPrincipalGroupInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(DetachRoleFromPrincipalGroupInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->andThrow(new PrincipalGroupNotFoundException());

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $action = new DetachRoleFromPrincipalGroupAction($useCase, $logger);

        $response = $action($request);
        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame(error_message('principal_group_not_found', 'en'), $payload['detail']);
    }
}
