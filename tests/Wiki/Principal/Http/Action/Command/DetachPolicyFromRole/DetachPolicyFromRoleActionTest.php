<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Http\Action\Command\DetachPolicyFromRole;

use Application\Http\Action\Wiki\Principal\Command\DetachPolicyFromRole\DetachPolicyFromRoleAction;
use Application\Http\Action\Wiki\Principal\Command\DetachPolicyFromRole\DetachPolicyFromRoleRequest;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Wiki\Principal\Application\Exception\RoleNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\DetachPolicyFromRole\DetachPolicyFromRoleInput;
use Source\Wiki\Principal\Application\UseCase\Command\DetachPolicyFromRole\DetachPolicyFromRoleInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DetachPolicyFromRoleActionTest extends TestCase
{
    public function testInvokeReturnsNoContentResponse(): void
    {
        /** @var DetachPolicyFromRoleRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(DetachPolicyFromRoleRequest::class);
        $request->shouldReceive('roleId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('policyIdentifier')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var DetachPolicyFromRoleInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(DetachPolicyFromRoleInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(Mockery::type(DetachPolicyFromRoleInput::class));

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $action = new DetachPolicyFromRoleAction($useCase, $logger);

        $response = $action($request);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    public function testInvokeReturnsNotFoundResponseWhenRoleNotFound(): void
    {
        /** @var DetachPolicyFromRoleRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(DetachPolicyFromRoleRequest::class);
        $request->shouldReceive('roleId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('policyIdentifier')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var DetachPolicyFromRoleInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(DetachPolicyFromRoleInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->andThrow(new RoleNotFoundException());

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $action = new DetachPolicyFromRoleAction($useCase, $logger);

        $response = $action($request);
        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame(error_message('role_not_found', 'en'), $payload['detail']);
    }
}
