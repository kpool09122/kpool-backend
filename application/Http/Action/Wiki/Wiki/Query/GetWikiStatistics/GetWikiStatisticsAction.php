<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\GetWikiStatistics;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\GetWikiStatistics\GetWikiStatisticsInterface;
use Throwable;

readonly class GetWikiStatisticsAction
{
    public function __construct(private GetWikiStatisticsInterface $getWikiStatistics, private LoggerInterface $logger)
    {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(): JsonResponse
    {
        try {
            $statistics = $this->getWikiStatistics->process();
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json($statistics->toArray());
    }
}
