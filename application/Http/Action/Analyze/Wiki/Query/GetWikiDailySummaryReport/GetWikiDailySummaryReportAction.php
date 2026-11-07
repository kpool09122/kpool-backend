<?php

declare(strict_types=1);

namespace Application\Http\Action\Analyze\Wiki\Query\GetWikiDailySummaryReport;

use Application\Http\Context\ActorContext;
use Application\Http\Context\AnalyzeContext;
use Application\Http\Exceptions\BadRequestHttpException;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Analyze\Shared\Domain\Exception\UnauthorizedException;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Application\UseCase\Query\GetWikiDailySummaryReport\GetWikiDailySummaryReportInput;
use Source\Analyze\Wiki\Application\UseCase\Query\GetWikiDailySummaryReport\GetWikiDailySummaryReportInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class GetWikiDailySummaryReportAction
{
    public function __construct(
        private GetWikiDailySummaryReportInterface $getWikiDailySummaryReport,
        private ActorContext $actorContext,
        private AnalyzeContext $analyzeContext,
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(GetWikiDailySummaryReportRequest $request): JsonResponse
    {
        try {
            try {
                $period = new ReportingPeriod(new UtcDate($request->from()), new UtcDate($request->to()), Carbon::now('UTC')->toDateTimeImmutable());
                $readModel = $this->getWikiDailySummaryReport->process(new GetWikiDailySummaryReportInput($this->analyzeContext->principalIdentifier, $period, $request->wikiIdentifier(), $request->translationSetIdentifier(), $request->language(), $request->country() === 'unknown' ? '' : $request->country()));
            } catch (UnauthorizedException $e) {
                throw new ForbiddenHttpException(detail: error_message('unauthorized', $this->actorContext->language->value), previous: $e);
            } catch (InvalidArgumentException $e) {
                throw new BadRequestHttpException(detail: error_message('invalid_analysis_period', $this->actorContext->language->value), previous: $e);
            }
        } catch (ForbiddenHttpException|BadRequestHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: error_message('internal_server_error', $this->actorContext->language->value), previous: $e);
        }

        return response()->json($readModel->toArray(), Response::HTTP_OK);
    }
}
