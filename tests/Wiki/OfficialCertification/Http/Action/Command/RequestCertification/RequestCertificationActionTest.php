<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Http\Action\Command\RequestCertification;

use Application\Http\Action\Wiki\OfficialCertification\Command\RequestCertification\RequestCertificationAction;
use Application\Http\Action\Wiki\OfficialCertification\Command\RequestCertification\RequestCertificationRequest;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationAlreadyRequestedException;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification\RequestCertificationInput;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification\RequestCertificationInterface;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification\RequestCertificationOutput;
use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationIdentifier;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RequestCertificationActionTest extends TestCase
{
    /**
     * 正常系: 申請成功時にHTTP 201を返すこと.
     */
    public function testInvokeReturnsCreatedResponse(): void
    {
        $certificationIdentifier = StrTestHelper::generateUuid();
        $wikiIdentifier = StrTestHelper::generateUuid();
        $ownerAccountIdentifier = StrTestHelper::generateUuid();

        /** @var RequestCertificationRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(RequestCertificationRequest::class);
        $request->shouldReceive('resourceType')->andReturn(ResourceType::AGENCY->value);
        $request->shouldReceive('wikiId')->andReturn($wikiIdentifier);
        $request->shouldReceive('ownerAccountId')->andReturn($ownerAccountIdentifier);
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var RequestCertificationInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(RequestCertificationInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(
                Mockery::type(RequestCertificationInput::class),
                Mockery::on(function ($output) use ($certificationIdentifier, $wikiIdentifier, $ownerAccountIdentifier): bool {
                    if (! $output instanceof RequestCertificationOutput) {
                        return false;
                    }

                    $output->setOfficialCertification(new OfficialCertification(
                        new CertificationIdentifier($certificationIdentifier),
                        ResourceType::AGENCY,
                        new WikiIdentifier($wikiIdentifier),
                        new AccountIdentifier($ownerAccountIdentifier),
                        CertificationStatus::PENDING,
                        new DateTimeImmutable(),
                        null,
                        null,
                    ));

                    return true;
                })
            );

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $action = new RequestCertificationAction($useCase, $logger);

        $response = $action($request);
        $payload = $response->getData(true);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame($certificationIdentifier, $payload['certificationIdentifier']);
        $this->assertSame(ResourceType::AGENCY->value, $payload['resourceType']);
        $this->assertSame($wikiIdentifier, $payload['wikiIdentifier']);
        $this->assertSame(CertificationStatus::PENDING->value, $payload['status']);
    }

    /**
     * 異常系: 既に申請済みの場合にHTTP 409を返すこと.
     */
    public function testInvokeReturnsConflictResponseWhenAlreadyRequested(): void
    {
        /** @var RequestCertificationRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(RequestCertificationRequest::class);
        $request->shouldReceive('resourceType')->andReturn(ResourceType::AGENCY->value);
        $request->shouldReceive('wikiId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('ownerAccountId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var RequestCertificationInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(RequestCertificationInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->andThrow(new OfficialCertificationAlreadyRequestedException());

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $action = new RequestCertificationAction($useCase, $logger);

        $response = $action($request);
        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertSame(error_message('official_certification_already_requested', 'en'), $payload['detail']);
    }
}
