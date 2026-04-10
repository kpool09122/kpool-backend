<?php

declare(strict_types=1);

namespace Tests\Account\Affiliation\Http\Action\Command\RequestAffiliation;

use Application\Http\Action\Account\Affiliation\Command\RequestAffiliation\RequestAffiliationAction;
use Application\Http\Action\Account\Affiliation\Command\RequestAffiliation\RequestAffiliationRequest;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Account\Affiliation\Application\Exception\AffiliationAlreadyExistsException;
use Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation\RequestAffiliationInput;
use Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation\RequestAffiliationInterface;
use Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation\RequestAffiliationOutput;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationTerms;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RequestAffiliationActionTest extends TestCase
{
    public function testInvokeReturnsCreatedResponse(): void
    {
        $affiliation = $this->createAffiliation();
        /** @var RequestAffiliationRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(RequestAffiliationRequest::class);
        $request->shouldReceive('agencyAccountIdentifier')->andReturn((string) $affiliation->agencyAccountIdentifier());
        $request->shouldReceive('talentAccountIdentifier')->andReturn((string) $affiliation->talentAccountIdentifier());
        $request->shouldReceive('requestedBy')->andReturn((string) $affiliation->requestedBy());
        $request->shouldReceive('terms')->andReturn([
            'revenueSharePercentage' => 30,
            'contractNotes' => 'Contract notes',
        ]);
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var RequestAffiliationInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(RequestAffiliationInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(
                Mockery::type(RequestAffiliationInput::class),
                Mockery::on(function ($output) use ($affiliation): bool {
                    if (! $output instanceof RequestAffiliationOutput) {
                        return false;
                    }

                    $output->setAffiliation($affiliation);

                    return true;
                })
            );

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $action = new RequestAffiliationAction($useCase, $logger);

        $response = $action($request);
        $payload = $response->getData(true);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame((string) $affiliation->affiliationIdentifier(), $payload['affiliationIdentifier']);
        $this->assertSame(AffiliationStatus::PENDING->value, $payload['status']);
    }

    public function testInvokeReturnsConflictResponseWhenAffiliationAlreadyExists(): void
    {
        /** @var RequestAffiliationRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(RequestAffiliationRequest::class);
        $request->shouldReceive('agencyAccountIdentifier')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('talentAccountIdentifier')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('requestedBy')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('terms')->andReturn(null);
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var RequestAffiliationInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(RequestAffiliationInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->andThrow(new AffiliationAlreadyExistsException('An active affiliation already exists between these accounts.'));

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $action = new RequestAffiliationAction($useCase, $logger);

        $response = $action($request);
        $payload = $response->getData(true);

        $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertSame(Response::HTTP_CONFLICT, $payload['status']);
        $this->assertSame(error_message('affiliation_already_exists', 'en'), $payload['detail']);
    }

    private function createAffiliation(): Affiliation
    {
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        return new Affiliation(
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $agencyAccountIdentifier,
            AffiliationStatus::PENDING,
            new AffiliationTerms(new Percentage(30), 'Contract notes'),
            new DateTimeImmutable('-1 day'),
            null,
            null,
        );
    }
}
