<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Http\Action\Command\DeletePrincipalGroup;

use Application\Http\Action\Wiki\Principal\Command\DeletePrincipalGroup\DeletePrincipalGroupAction;
use Application\Http\Action\Wiki\Principal\Command\DeletePrincipalGroup\DeletePrincipalGroupRequest;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Wiki\Principal\Application\Exception\CannotDeleteDefaultPrincipalGroupException;
use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\DeletePrincipalGroup\DeletePrincipalGroupInput;
use Source\Wiki\Principal\Application\UseCase\Command\DeletePrincipalGroup\DeletePrincipalGroupInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DeletePrincipalGroupActionTest extends TestCase
{
    public function testInvokeReturnsNoContentResponse(): void
    {
        /** @var DeletePrincipalGroupRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(DeletePrincipalGroupRequest::class);
        $request->shouldReceive('principalGroupId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var DeletePrincipalGroupInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(DeletePrincipalGroupInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(Mockery::type(DeletePrincipalGroupInput::class));

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $action = new DeletePrincipalGroupAction($useCase, $logger);

        $response = $action($request);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    public function testInvokeReturnsNotFoundResponse(): void
    {
        /** @var DeletePrincipalGroupRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(DeletePrincipalGroupRequest::class);
        $request->shouldReceive('principalGroupId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var DeletePrincipalGroupInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(DeletePrincipalGroupInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->andThrow(new PrincipalGroupNotFoundException());

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $action = new DeletePrincipalGroupAction($useCase, $logger);

        $response = $action($request);
        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame(error_message('principal_group_not_found', 'en'), $payload['detail']);
    }

    public function testInvokeReturnsConflictResponseWhenDefaultGroup(): void
    {
        /** @var DeletePrincipalGroupRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(DeletePrincipalGroupRequest::class);
        $request->shouldReceive('principalGroupId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var DeletePrincipalGroupInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(DeletePrincipalGroupInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->andThrow(new CannotDeleteDefaultPrincipalGroupException());

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $action = new DeletePrincipalGroupAction($useCase, $logger);

        $response = $action($request);
        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertSame(error_message('cannot_delete_default_principal_group', 'en'), $payload['detail']);
    }
}
