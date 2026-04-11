<?php

declare(strict_types=1);

namespace Tests\Wiki\OfficialCertification\Http\Action\Command\RejectCertification;

use Application\Http\Action\Wiki\OfficialCertification\Command\RejectCertification\RejectCertificationAction;
use Application\Http\Action\Wiki\OfficialCertification\Command\RejectCertification\RejectCertificationRequest;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationInvalidStatusException;
use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationNotFoundException;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RejectCertification\RejectCertificationInput;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RejectCertification\RejectCertificationInterface;
use Source\Wiki\OfficialCertification\Application\UseCase\Command\RejectCertification\RejectCertificationOutput;
use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationIdentifier;
use Source\Wiki\OfficialCertification\Domain\ValueObject\CertificationStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RejectCertificationActionTest extends TestCase
{
    /**
     * 正常系: 却下成功時にHTTP 200を返すこと.
     */
    public function testInvokeReturnsOkResponse(): void
    {
        $certificationIdentifier = StrTestHelper::generateUuid();
        $wikiIdentifier = StrTestHelper::generateUuid();

        /** @var RejectCertificationRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(RejectCertificationRequest::class);
        $request->shouldReceive('certificationId')->andReturn($certificationIdentifier);
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var RejectCertificationInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(RejectCertificationInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->with(
                Mockery::type(RejectCertificationInput::class),
                Mockery::on(function ($output) use ($certificationIdentifier, $wikiIdentifier): bool {
                    if (! $output instanceof RejectCertificationOutput) {
                        return false;
                    }

                    $output->setOfficialCertification(new OfficialCertification(
                        new CertificationIdentifier($certificationIdentifier),
                        ResourceType::GROUP,
                        new WikiIdentifier($wikiIdentifier),
                        new AccountIdentifier(StrTestHelper::generateUuid()),
                        CertificationStatus::REJECTED,
                        new DateTimeImmutable(),
                        null,
                        new DateTimeImmutable(),
                    ));

                    return true;
                })
            );

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $action = new RejectCertificationAction($useCase, $logger);

        $response = $action($request);
        $payload = $response->getData(true);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame($certificationIdentifier, $payload['certificationIdentifier']);
        $this->assertSame(ResourceType::GROUP->value, $payload['resourceType']);
        $this->assertSame($wikiIdentifier, $payload['wikiIdentifier']);
        $this->assertSame(CertificationStatus::REJECTED->value, $payload['status']);
    }

    /**
     * 異常系: 公式認定が見つからない場合にHTTP 404を返すこと.
     */
    public function testInvokeReturnsNotFoundResponseWhenCertificationNotFound(): void
    {
        /** @var RejectCertificationRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(RejectCertificationRequest::class);
        $request->shouldReceive('certificationId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var RejectCertificationInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(RejectCertificationInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->andThrow(new OfficialCertificationNotFoundException());

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $action = new RejectCertificationAction($useCase, $logger);

        $response = $action($request);
        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame(error_message('official_certification_not_found', 'en'), $payload['detail']);
    }

    /**
     * 異常系: ステータスが無効な場合にHTTP 409を返すこと.
     */
    public function testInvokeReturnsConflictResponseWhenInvalidStatus(): void
    {
        /** @var RejectCertificationRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(RejectCertificationRequest::class);
        $request->shouldReceive('certificationId')->andReturn(StrTestHelper::generateUuid());
        $request->shouldReceive('language')->andReturn('en');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        /** @var RejectCertificationInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(RejectCertificationInterface::class);
        $useCase->shouldReceive('process')
            ->once()
            ->andThrow(new OfficialCertificationInvalidStatusException());

        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();

        $action = new RejectCertificationAction($useCase, $logger);

        $response = $action($request);
        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertSame(error_message('official_certification_invalid_status', 'en'), $payload['detail']);
    }
}
