<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Http\Action\Command\CreateRole;

use Application\Http\Action\Wiki\Principal\Command\CreateRole\CreateRoleAction;
use Application\Http\Action\Wiki\Principal\Command\CreateRole\CreateRoleRequest;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Wiki\Principal\Application\UseCase\Command\CreateRole\CreateRoleInput;
use Source\Wiki\Principal\Application\UseCase\Command\CreateRole\CreateRoleInterface;
use Source\Wiki\Principal\Application\UseCase\Command\CreateRole\CreateRoleOutput;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreateRoleActionTest extends TestCase
{
    public function testInvokeReturnsCreatedResponse(): void
    {
        $roleIdentifier = StrTestHelper::generateUuid();

        /** @var CreateRoleRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(CreateRoleRequest::class);
        $request->shouldReceive('name')->andReturn('Test Role');
        $request->shouldReceive('policies')->andReturn(null);
        $request->shouldReceive('isSystemRole')->andReturn(false);
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var CreateRoleInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(CreateRoleInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(
                Mockery::type(CreateRoleInput::class),
                Mockery::on(function ($output) use ($roleIdentifier): bool {
                    if (! $output instanceof CreateRoleOutput) {
                        return false;
                    }

                    $output->setRole(new Role(
                        new RoleIdentifier($roleIdentifier),
                        'Test Role',
                        [],
                        false,
                        new DateTimeImmutable(),
                    ));

                    return true;
                })
            );

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $action = new CreateRoleAction($useCase, $logger);

        $response = $action($request);
        $payload = $response->getData(true);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame($roleIdentifier, $payload['roleIdentifier']);
    }
}
