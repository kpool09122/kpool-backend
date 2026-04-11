<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Http\Action\Command\CreatePrincipal;

use Application\Http\Action\Wiki\Principal\Command\CreatePrincipal\CreatePrincipalAction;
use Application\Http\Action\Wiki\Principal\Command\CreatePrincipal\CreatePrincipalRequest;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInput;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInterface;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalOutput;
use Source\Wiki\Principal\Domain\Exception\PrincipalAlreadyExistsException;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreatePrincipalActionTest extends TestCase
{
    public function testInvokeReturnsCreatedResponse(): void
    {
        $identityIdentifier = StrTestHelper::generateUuid();
        $principalIdentifier = StrTestHelper::generateUuid();

        /** @var CreatePrincipalRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(CreatePrincipalRequest::class);
        $request->shouldReceive('identityIdentifier')->andReturn($identityIdentifier);
        $request->shouldReceive('accountIdentifier')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var CreatePrincipalInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(CreatePrincipalInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(
                Mockery::type(CreatePrincipalInput::class),
                Mockery::on(function ($output) use ($principalIdentifier, $identityIdentifier): bool {
                    if (! $output instanceof CreatePrincipalOutput) {
                        return false;
                    }

                    $output->setPrincipal(new \Source\Wiki\Principal\Domain\Entity\Principal(
                        new \Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier($principalIdentifier),
                        new \Source\Shared\Domain\ValueObject\IdentityIdentifier($identityIdentifier),
                        null,
                        [],
                        [],
                    ));

                    return true;
                })
            );

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $action = new CreatePrincipalAction($useCase, $logger);

        $response = $action($request);
        $payload = $response->getData(true);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame($principalIdentifier, $payload['principalIdentifier']);
        $this->assertSame($identityIdentifier, $payload['identityIdentifier']);
    }

    public function testInvokeReturnsConflictResponseWhenPrincipalAlreadyExists(): void
    {
        /** @var CreatePrincipalRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(CreatePrincipalRequest::class);
        $request->shouldReceive('identityIdentifier')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('accountIdentifier')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var CreatePrincipalInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(CreatePrincipalInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->andThrow(new PrincipalAlreadyExistsException());

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $action = new CreatePrincipalAction($useCase, $logger);

        $response = $action($request);
        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertSame(error_message('principal_already_exists', 'en'), $payload['detail']);
    }
}
