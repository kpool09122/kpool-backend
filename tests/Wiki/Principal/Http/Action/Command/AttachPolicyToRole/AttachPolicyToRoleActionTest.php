<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Http\Action\Command\AttachPolicyToRole;

use Application\Http\Action\Wiki\Principal\Command\AttachPolicyToRole\AttachPolicyToRoleAction;
use Application\Http\Action\Wiki\Principal\Command\AttachPolicyToRole\AttachPolicyToRoleRequest;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Wiki\Principal\Application\Exception\PolicyNotFoundException;
use Source\Wiki\Principal\Application\Exception\RoleNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\AttachPolicyToRole\AttachPolicyToRoleInput;
use Source\Wiki\Principal\Application\UseCase\Command\AttachPolicyToRole\AttachPolicyToRoleInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AttachPolicyToRoleActionTest extends TestCase
{
    public function testInvokeReturnsNoContentResponse(): void
    {
        /** @var AttachPolicyToRoleRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(AttachPolicyToRoleRequest::class);
        $request->shouldReceive('roleId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('policyIdentifier')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var AttachPolicyToRoleInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(AttachPolicyToRoleInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(Mockery::type(AttachPolicyToRoleInput::class));

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $action = new AttachPolicyToRoleAction($useCase, $logger);

        $response = $action($request);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    public function testInvokeReturnsNotFoundResponseWhenRoleNotFound(): void
    {
        /** @var AttachPolicyToRoleRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(AttachPolicyToRoleRequest::class);
        $request->shouldReceive('roleId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('policyIdentifier')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var AttachPolicyToRoleInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(AttachPolicyToRoleInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->andThrow(new RoleNotFoundException());

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $action = new AttachPolicyToRoleAction($useCase, $logger);

        $response = $action($request);
        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame(error_message('role_not_found', 'en'), $payload['detail']);
    }

    public function testInvokeReturnsNotFoundResponseWhenPolicyNotFound(): void
    {
        /** @var AttachPolicyToRoleRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(AttachPolicyToRoleRequest::class);
        $request->shouldReceive('roleId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('policyIdentifier')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var AttachPolicyToRoleInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(AttachPolicyToRoleInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->andThrow(new PolicyNotFoundException());

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $action = new AttachPolicyToRoleAction($useCase, $logger);

        $response = $action($request);
        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame(error_message('policy_not_found', 'en'), $payload['detail']);
    }
}
