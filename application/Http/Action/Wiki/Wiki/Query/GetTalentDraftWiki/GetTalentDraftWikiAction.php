<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\GetTalentDraftWiki;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\GetTalentDraftWiki\GetTalentDraftWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetTalentDraftWiki\GetTalentDraftWikiInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class GetTalentDraftWikiAction
{
    public function __construct(
        private GetTalentDraftWikiInterface $getTalentDraftWiki,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(GetTalentDraftWikiRequest $request): JsonResponse
    {
        try {
            try {
                $input = new GetTalentDraftWikiInput(
                    new Slug($request->slug()),
                    Language::from($request->language()),
                );
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            try {
                $readModel = $this->getTalentDraftWiki->process($input);
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
