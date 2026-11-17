<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\GetTalentDraftWiki;

use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\GetTalentDraftWiki\GetTalentDraftWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetTalentDraftWiki\GetTalentDraftWikiInterface;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class GetTalentDraftWikiAction
{
    public function __construct(
        private GetTalentDraftWikiInterface $getTalentDraftWiki,
        private WikiContext $wikiContext,
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
                    new DraftWikiIdentifier($request->wikiIdentifier()),
                    $this->wikiContext->principalIdentifier,
                );
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            try {
                $readModel = $this->getTalentDraftWiki->process($input);
            } catch (DisallowedException $e) {
                throw new ForbiddenHttpException(detail: error_message('disallowed', $request->language()), previous: $e);
            } catch (PrincipalNotFoundException $e) {
                throw new NotFoundHttpException(detail: error_message('principal_not_found', $request->language()), previous: $e);
            } catch (WikiNotFoundException $e) {
                throw new NotFoundHttpException(detail: 'Draft wiki not found.', previous: $e);
            }
        } catch (ForbiddenHttpException|NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($readModel->toArray(), Response::HTTP_OK);
    }
}
