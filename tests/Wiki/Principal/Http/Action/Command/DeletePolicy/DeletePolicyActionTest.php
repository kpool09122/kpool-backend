<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Http\Action\Command\DeletePolicy;

use Application\Http\Action\Wiki\Principal\Command\DeletePolicy\DeletePolicyAction;
use Application\Http\Action\Wiki\Principal\Command\DeletePolicy\DeletePolicyRequest;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Wiki\Principal\Application\Exception\CannotDeleteSystemPolicyException;
use Source\Wiki\Principal\Application\Exception\PolicyNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\DeletePolicy\DeletePolicyInput;
use Source\Wiki\Principal\Application\UseCase\Command\DeletePolicy\DeletePolicyInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DeletePolicyActionTest extends TestCase
{
    public function testInvokeReturnsNoContentResponse(): void
    {
        /** @var DeletePolicyRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(DeletePolicyRequest::class);
        $request->shouldReceive('policyId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var DeletePolicyInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(DeletePolicyInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(Mockery::type(DeletePolicyInput::class));

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $action = new DeletePolicyAction($useCase, $logger);

        $response = $action($request);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    public function testInvokeReturnsNotFoundResponse(): void
    {
        /** @var DeletePolicyRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(DeletePolicyRequest::class);
        $request->shouldReceive('policyId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var DeletePolicyInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(DeletePolicyInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->andThrow(new PolicyNotFoundException());

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $action = new DeletePolicyAction($useCase, $logger);

        $response = $action($request);
        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame(error_message('policy_not_found', 'en'), $payload['detail']);
    }

    public function testInvokeReturnsConflictResponseWhenSystemPolicy(): void
    {
        /** @var DeletePolicyRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(DeletePolicyRequest::class);
        $request->shouldReceive('policyId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var DeletePolicyInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(DeletePolicyInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->andThrow(new CannotDeleteSystemPolicyException());

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $action = new DeletePolicyAction($useCase, $logger);

        $response = $action($request);
        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertSame(error_message('cannot_delete_system_policy', 'en'), $payload['detail']);
    }
}
