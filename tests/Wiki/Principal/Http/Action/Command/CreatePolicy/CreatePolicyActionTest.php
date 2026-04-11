<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Http\Action\Command\CreatePolicy;

use Application\Http\Action\Wiki\Principal\Command\CreatePolicy\CreatePolicyAction;
use Application\Http\Action\Wiki\Principal\Command\CreatePolicy\CreatePolicyRequest;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePolicy\CreatePolicyInput;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePolicy\CreatePolicyInterface;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePolicy\CreatePolicyOutput;
use Source\Wiki\Principal\Domain\Entity\Policy;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreatePolicyActionTest extends TestCase
{
    public function testInvokeReturnsCreatedResponse(): void
    {
        $policyIdentifier = StrTestHelper::generateUuid();

        /** @var CreatePolicyRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(CreatePolicyRequest::class);
        $request->shouldReceive('name')->andReturn('Test Policy');
        $request->shouldReceive('statements')->andReturn([
            [
                'effect' => 'allow',
                'actions' => ['create'],
                'resourceTypes' => ['agency'],
            ],
        ]);
        $request->shouldReceive('isSystemPolicy')->andReturn(false);
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var CreatePolicyInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(CreatePolicyInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(
                Mockery::type(CreatePolicyInput::class),
                Mockery::on(function ($output) use ($policyIdentifier): bool {
                    if (! $output instanceof CreatePolicyOutput) {
                        return false;
                    }

                    $output->setPolicy(new Policy(
                        new PolicyIdentifier($policyIdentifier),
                        'Test Policy',
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

        $action = new CreatePolicyAction($useCase, $logger);

        $response = $action($request);
        $payload = $response->getData(true);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame($policyIdentifier, $payload['policyIdentifier']);
    }
}
