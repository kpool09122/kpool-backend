<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Http\Action\Command\RemovePrincipalFromPrincipalGroup;

use Application\Http\Action\Wiki\Principal\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupAction;
use Application\Http\Action\Wiki\Principal\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupRequest;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupInput;
use Source\Wiki\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupInterface;
use Source\Wiki\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupOutput;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Exception\PrincipalNotMemberException;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RemovePrincipalFromPrincipalGroupActionTest extends TestCase
{
    public function testInvokeReturnsOkResponse(): void
    {
        $principalGroupIdentifier = StrTestHelper::generateUuid();

        /** @var RemovePrincipalFromPrincipalGroupRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(RemovePrincipalFromPrincipalGroupRequest::class);
        $request->shouldReceive('principalGroupId')->andReturn($principalGroupIdentifier);
        $request->shouldReceive('principalIdentifier')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var RemovePrincipalFromPrincipalGroupInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(RemovePrincipalFromPrincipalGroupInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(
                Mockery::type(RemovePrincipalFromPrincipalGroupInput::class),
                Mockery::on(function ($output) use ($principalGroupIdentifier): bool {
                    if (! $output instanceof RemovePrincipalFromPrincipalGroupOutput) {
                        return false;
                    }

                    $output->setPrincipalGroup(new PrincipalGroup(
                        new PrincipalGroupIdentifier($principalGroupIdentifier),
                        new AccountIdentifier(StrTestHelper::generateUuid()),
                        'Test Group',
                        false,
                        new DateTimeImmutable(),
                    ));

                    return true;
                })
            );

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $action = new RemovePrincipalFromPrincipalGroupAction($useCase, $logger);

        $response = $action($request);
        $payload = $response->getData(true);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame($principalGroupIdentifier, $payload['principalGroupIdentifier']);
    }

    public function testInvokeReturnsNotFoundResponse(): void
    {
        /** @var RemovePrincipalFromPrincipalGroupRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(RemovePrincipalFromPrincipalGroupRequest::class);
        $request->shouldReceive('principalGroupId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('principalIdentifier')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var RemovePrincipalFromPrincipalGroupInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(RemovePrincipalFromPrincipalGroupInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->andThrow(new PrincipalGroupNotFoundException());

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $action = new RemovePrincipalFromPrincipalGroupAction($useCase, $logger);

        $response = $action($request);
        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame(error_message('principal_group_not_found', 'en'), $payload['detail']);
    }

    public function testInvokeReturnsConflictResponseWhenNotMember(): void
    {
        /** @var RemovePrincipalFromPrincipalGroupRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(RemovePrincipalFromPrincipalGroupRequest::class);
        $request->shouldReceive('principalGroupId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('principalIdentifier')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var RemovePrincipalFromPrincipalGroupInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(RemovePrincipalFromPrincipalGroupInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->andThrow(new PrincipalNotMemberException());

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $action = new RemovePrincipalFromPrincipalGroupAction($useCase, $logger);

        $response = $action($request);
        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertSame(error_message('principal_not_member', 'en'), $payload['detail']);
    }
}
