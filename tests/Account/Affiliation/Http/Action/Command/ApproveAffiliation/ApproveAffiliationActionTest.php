<?php

declare(strict_types=1);

namespace Tests\Account\Affiliation\Http\Action\Command\ApproveAffiliation;

use Application\Http\Action\Account\Affiliation\Command\ApproveAffiliation\ApproveAffiliationAction;
use Application\Http\Action\Account\Affiliation\Command\ApproveAffiliation\ApproveAffiliationRequest;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Account\Affiliation\Application\Exception\DisallowedAffiliationOperationException;
use Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation\ApproveAffiliationInput;
use Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation\ApproveAffiliationInterface;
use Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation\ApproveAffiliationOutput;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationStatus;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationTerms;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ApproveAffiliationActionTest extends TestCase
{
    public function testInvokeReturnsOkResponse(): void
    {
        $affiliation = $this->createAffiliation();
        /** @var ApproveAffiliationRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(ApproveAffiliationRequest::class);
        $request->shouldReceive('affiliationId')->andReturn((string) $affiliation->affiliationIdentifier());
        $request->shouldReceive('approverAccountIdentifier')->andReturn((string) $affiliation->approverAccountIdentifier());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var ApproveAffiliationInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(ApproveAffiliationInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(
                Mockery::type(ApproveAffiliationInput::class),
                Mockery::on(function ($output) use ($affiliation): bool {
                    if (! $output instanceof ApproveAffiliationOutput) {
                        return false;
                    }

                    $output->setAffiliation($affiliation);

                    return true;
                })
            );

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $action = new ApproveAffiliationAction($useCase, $logger);

        $response = $action($request);
        $payload = $response->getData(true);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(AffiliationStatus::ACTIVE->value, $payload['status']);
    }

    public function testInvokeReturnsForbiddenResponse(): void
    {
        /** @var ApproveAffiliationRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(ApproveAffiliationRequest::class);
        $request->shouldReceive('affiliationId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('approverAccountIdentifier')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var ApproveAffiliationInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(ApproveAffiliationInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->andThrow(new DisallowedAffiliationOperationException('Only the designated approver can approve this affiliation.'));

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $action = new ApproveAffiliationAction($useCase, $logger);

        $response = $action($request);
        $payload = $response->getData(true);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertSame(error_message('disallowed_affiliation_operation', 'en'), $payload['detail']);
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
            AffiliationStatus::ACTIVE,
            new AffiliationTerms(new Percentage(25), 'Approved notes'),
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable(),
            null,
        );
    }
}
