<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Http\Action\Command\DeleteRole;

use Application\Http\Action\Wiki\Principal\Command\DeleteRole\DeleteRoleAction;
use Application\Http\Action\Wiki\Principal\Command\DeleteRole\DeleteRoleRequest;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Wiki\Principal\Application\Exception\CannotDeleteSystemRoleException;
use Source\Wiki\Principal\Application\Exception\RoleNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\DeleteRole\DeleteRoleInput;
use Source\Wiki\Principal\Application\UseCase\Command\DeleteRole\DeleteRoleInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DeleteRoleActionTest extends TestCase
{
    public function testInvokeReturnsNoContentResponse(): void
    {
        /** @var DeleteRoleRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(DeleteRoleRequest::class);
        $request->shouldReceive('roleId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var DeleteRoleInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(DeleteRoleInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(Mockery::type(DeleteRoleInput::class));

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $action = new DeleteRoleAction($useCase, $logger);

        $response = $action($request);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    public function testInvokeReturnsNotFoundResponse(): void
    {
        /** @var DeleteRoleRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(DeleteRoleRequest::class);
        $request->shouldReceive('roleId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var DeleteRoleInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(DeleteRoleInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->andThrow(new RoleNotFoundException());

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $action = new DeleteRoleAction($useCase, $logger);

        $response = $action($request);
        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame(error_message('role_not_found', 'en'), $payload['detail']);
    }

    public function testInvokeReturnsConflictResponseWhenSystemRole(): void
    {
        /** @var DeleteRoleRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(DeleteRoleRequest::class);
        $request->shouldReceive('roleId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var DeleteRoleInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(DeleteRoleInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->andThrow(new CannotDeleteSystemRoleException());

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $action = new DeleteRoleAction($useCase, $logger);

        $response = $action($request);
        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertSame(error_message('cannot_delete_system_role', 'en'), $payload['detail']);
    }
}
