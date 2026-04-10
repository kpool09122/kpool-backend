<?php

declare(strict_types=1);

namespace Tests\Account\Affiliation\Http\Action\Command\RejectAffiliation;

use Application\Http\Action\Account\Affiliation\Command\RejectAffiliation\RejectAffiliationAction;
use Application\Http\Action\Account\Affiliation\Command\RejectAffiliation\RejectAffiliationRequest;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Account\Affiliation\Application\Exception\AffiliationNotFoundException;
use Source\Account\Affiliation\Application\UseCase\Command\RejectAffiliation\RejectAffiliationInput;
use Source\Account\Affiliation\Application\UseCase\Command\RejectAffiliation\RejectAffiliationInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectAffiliationActionTest extends TestCase
{
    public function testInvokeReturnsNoContentResponse(): void
    {
        /** @var RejectAffiliationRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(RejectAffiliationRequest::class);
        $request->shouldReceive('affiliationId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('rejectorAccountIdentifier')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var RejectAffiliationInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(RejectAffiliationInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(Mockery::type(RejectAffiliationInput::class));

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $action = new RejectAffiliationAction($useCase, $logger);

        $response = $action($request);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    public function testInvokeReturnsNotFoundResponse(): void
    {
        /** @var RejectAffiliationRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(RejectAffiliationRequest::class);
        $request->shouldReceive('affiliationId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('rejectorAccountIdentifier')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var RejectAffiliationInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(RejectAffiliationInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->andThrow(new AffiliationNotFoundException('Affiliation not found.'));

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $action = new RejectAffiliationAction($useCase, $logger);

        $response = $action($request);
        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame(error_message('affiliation_not_found', 'en'), $payload['detail']);
    }
}
