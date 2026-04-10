<?php

declare(strict_types=1);

namespace Tests\Account\Affiliation\Http\Action\Command\TerminateAffiliation;

use Application\Http\Action\Account\Affiliation\Command\TerminateAffiliation\TerminateAffiliationAction;
use Application\Http\Action\Account\Affiliation\Command\TerminateAffiliation\TerminateAffiliationRequest;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Account\Affiliation\Application\Exception\AffiliationNotFoundException;
use Source\Account\Affiliation\Application\UseCase\Command\TerminateAffiliation\TerminateAffiliationInput;
use Source\Account\Affiliation\Application\UseCase\Command\TerminateAffiliation\TerminateAffiliationInterface;
use Source\Account\Affiliation\Application\UseCase\Command\TerminateAffiliation\TerminateAffiliationOutput;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationTerms;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class TerminateAffiliationActionTest extends TestCase
{
    public function testInvokeReturnsOkResponse(): void
    {
        $affiliation = $this->createAffiliation();
        /** @var TerminateAffiliationRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(TerminateAffiliationRequest::class);
        $request->shouldReceive('affiliationId')->andReturn((string) $affiliation->affiliationIdentifier());
        $request->shouldReceive('terminatorAccountIdentifier')->andReturn((string) $affiliation->agencyAccountIdentifier());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var TerminateAffiliationInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(TerminateAffiliationInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(
                Mockery::type(TerminateAffiliationInput::class),
                Mockery::on(function ($output) use ($affiliation): bool {
                    if (! $output instanceof TerminateAffiliationOutput) {
                        return false;
                    }

                    $output->setAffiliation($affiliation);

                    return true;
                })
            );

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $action = new TerminateAffiliationAction($useCase, $logger);

        $response = $action($request);
        $payload = $response->getData(true);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(AffiliationStatus::TERMINATED->value, $payload['status']);
    }

    public function testInvokeReturnsNotFoundResponse(): void
    {
        /** @var TerminateAffiliationRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(TerminateAffiliationRequest::class);
        $request->shouldReceive('affiliationId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('terminatorAccountIdentifier')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var TerminateAffiliationInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(TerminateAffiliationInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->andThrow(new AffiliationNotFoundException('Affiliation not found.'));

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $action = new TerminateAffiliationAction($useCase, $logger);

        $response = $action($request);
        $payload = $response->getData(true);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame(error_message('affiliation_not_found', 'en'), $payload['detail']);
    }

    private function createAffiliation(): Affiliation
    {
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        return new Affiliation(
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            $agencyAccountIdentifier,
            new AccountIdentifier(StrTestHelper::generateUuid()),
            $agencyAccountIdentifier,
            AffiliationStatus::TERMINATED,
            new AffiliationTerms(new Percentage(20), 'Termination notes'),
            new DateTimeImmutable('-2 days'),
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable(),
        );
    }
}
