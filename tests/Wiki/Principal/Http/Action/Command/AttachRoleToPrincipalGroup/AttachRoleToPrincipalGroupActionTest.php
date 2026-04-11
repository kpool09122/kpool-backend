<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Http\Action\Command\AttachRoleToPrincipalGroup;

use Application\Http\Action\Wiki\Principal\Command\AttachRoleToPrincipalGroup\AttachRoleToPrincipalGroupAction;
use Application\Http\Action\Wiki\Principal\Command\AttachRoleToPrincipalGroup\AttachRoleToPrincipalGroupRequest;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Application\Exception\RoleNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\AttachRoleToPrincipalGroup\AttachRoleToPrincipalGroupInput;
use Source\Wiki\Principal\Application\UseCase\Command\AttachRoleToPrincipalGroup\AttachRoleToPrincipalGroupInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AttachRoleToPrincipalGroupActionTest extends TestCase
{
    public function testInvokeReturnsNoContentResponse(): void
    {
        /** @var AttachRoleToPrincipalGroupRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(AttachRoleToPrincipalGroupRequest::class);
        $request->shouldReceive('principalGroupId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('roleIdentifier')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var AttachRoleToPrincipalGroupInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(AttachRoleToPrincipalGroupInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(Mockery::type(AttachRoleToPrincipalGroupInput::class));

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $action = new AttachRoleToPrincipalGroupAction($useCase, $logger);

        $response = $action($request);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    public function testInvokeReturnsNotFoundResponseWhenPrincipalGroupNotFound(): void
    {
        /** @var AttachRoleToPrincipalGroupRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(AttachRoleToPrincipalGroupRequest::class);
        $request->shouldReceive('principalGroupId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('roleIdentifier')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var AttachRoleToPrincipalGroupInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(AttachRoleToPrincipalGroupInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->andThrow(new PrincipalGroupNotFoundException());

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $action = new AttachRoleToPrincipalGroupAction($useCase, $logger);

        $response = $action($request);
        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame(error_message('principal_group_not_found', 'en'), $payload['detail']);
    }

    public function testInvokeReturnsNotFoundResponseWhenRoleNotFound(): void
    {
        /** @var AttachRoleToPrincipalGroupRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(AttachRoleToPrincipalGroupRequest::class);
        $request->shouldReceive('principalGroupId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('roleIdentifier')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var AttachRoleToPrincipalGroupInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(AttachRoleToPrincipalGroupInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->andThrow(new RoleNotFoundException());

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $action = new AttachRoleToPrincipalGroupAction($useCase, $logger);

        $response = $action($request);
        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame(error_message('role_not_found', 'en'), $payload['detail']);
    }
}
