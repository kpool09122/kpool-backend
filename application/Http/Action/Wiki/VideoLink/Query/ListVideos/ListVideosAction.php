<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\VideoLink\Query\ListVideos;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos\ListVideosInput;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos\ListVideosInterface;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos\ListVideosOutput;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class ListVideosAction
{
    public function __construct(
        private ListVideosInterface $listVideos,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(ListVideosRequest $request): JsonResponse
    {
        try {
            try {
                $input = new ListVideosInput(
                    slug: new Slug($request->slug()),
                    language: Language::from($request->language()),
                    resourceType: ResourceType::from($request->resourceType()),
                    minDurationSeconds: $request->minDurationSeconds(),
                    maxDurationSeconds: $request->maxDurationSeconds(),
                );
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: error_message('invalid_video_list_query', $request->language()), previous: $e);
            }

            try {
                $output = new ListVideosOutput();
                $this->listVideos->process($input, $output);
            } catch (WikiNotFoundException $e) {
                throw new NotFoundHttpException(detail: error_message('published_wiki_not_found', $request->language()), previous: $e);
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: error_message('invalid_video_list_query', $request->language()), previous: $e);
            }
        } catch (NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
