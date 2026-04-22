<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\GetSongDraftWiki;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\GetSongDraftWiki\GetSongDraftWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetSongDraftWiki\GetSongDraftWikiInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class GetSongDraftWikiAction
{
    public function __construct(
        private GetSongDraftWikiInterface $getSongDraftWiki,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(GetSongDraftWikiRequest $request): JsonResponse
    {
        try {
            try {
                $input = new GetSongDraftWikiInput(
                    new Slug($request->slug()),
                    Language::from($request->language()),
                );
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            try {
                $readModel = $this->getSongDraftWiki->process($input);
            } catch (WikiNotFoundException $e) {
                throw new NotFoundHttpException(detail: 'Draft wiki not found.', previous: $e);
            }
        } catch (NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($readModel->toArray(), Response::HTTP_OK);
    }
}
