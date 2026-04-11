<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Http\Action\Command\CreatePrincipalGroup;

use Application\Http\Action\Wiki\Principal\Command\CreatePrincipalGroup\CreatePrincipalGroupAction;
use Application\Http\Action\Wiki\Principal\Command\CreatePrincipalGroup\CreatePrincipalGroupRequest;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipalGroup\CreatePrincipalGroupInput;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipalGroup\CreatePrincipalGroupInterface;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipalGroup\CreatePrincipalGroupOutput;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreatePrincipalGroupActionTest extends TestCase
{
    public function testInvokeReturnsCreatedResponse(): void
    {
        $principalGroupIdentifier = StrTestHelper::generateUuid();
        $accountIdentifier = StrTestHelper::generateUuid();

        /** @var CreatePrincipalGroupRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(CreatePrincipalGroupRequest::class);
        $request->shouldReceive('accountIdentifier')->andReturn($accountIdentifier);
        $request->shouldReceive('name')->andReturn('Test Group');
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var CreatePrincipalGroupInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(CreatePrincipalGroupInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(
                Mockery::type(CreatePrincipalGroupInput::class),
                Mockery::on(function ($output) use ($principalGroupIdentifier, $accountIdentifier): bool {
                    if (! $output instanceof CreatePrincipalGroupOutput) {
                        return false;
                    }

                    $output->setPrincipalGroup(new PrincipalGroup(
                        new PrincipalGroupIdentifier($principalGroupIdentifier),
                        new AccountIdentifier($accountIdentifier),
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

        $action = new CreatePrincipalGroupAction($useCase, $logger);

        $response = $action($request);
        $payload = $response->getData(true);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame($principalGroupIdentifier, $payload['principalGroupIdentifier']);
    }
}
