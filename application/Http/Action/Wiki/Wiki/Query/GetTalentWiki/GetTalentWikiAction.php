<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\GetTalentWiki;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\GetTalentWiki\GetTalentWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetTalentWiki\GetTalentWikiInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class GetTalentWikiAction
{
    public function __construct(
        private GetTalentWikiInterface $getTalentWiki,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(GetTalentWikiRequest $request): JsonResponse
    {
        try {
            try {
                $input = new GetTalentWikiInput(
                    new Slug($request->slug()),
                    Language::from($request->language()),
                );
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            try {
                $readModel = $this->getTalentWiki->process($input);
            } catch (WikiNotFoundException $e) {
                throw new NotFoundHttpException(detail: 'Wiki not found.', previous: $e);
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
