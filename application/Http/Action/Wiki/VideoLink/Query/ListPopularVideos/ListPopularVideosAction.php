<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\VideoLink\Query\ListPopularVideos;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListPopularVideos\ListPopularVideosInterface;
use Source\Wiki\VideoLink\Application\UseCase\Query\PopularVideoReadModel;
use Throwable;

readonly class ListPopularVideosAction
{
    public function __construct(private ListPopularVideosInterface $listPopularVideos, private LoggerInterface $logger)
    {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(): JsonResponse
    {
        try {
            $videos = $this->listPopularVideos->process();
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json(['videos' => array_map(static fn (PopularVideoReadModel $video): array => $video->toArray(), $videos)]);
    }
}
