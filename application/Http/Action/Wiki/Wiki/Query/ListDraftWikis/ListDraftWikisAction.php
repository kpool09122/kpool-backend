<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\ListDraftWikis;

use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis\ListDraftWikisInput;
use Source\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis\ListDraftWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListDraftWikis\ListDraftWikisOutput;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListDraftWikisAction
{
    public function __construct(
        private ListDraftWikisInterface $listDraftWikis,
        private WikiContext $wikiContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(ListDraftWikisRequest $request): JsonResponse
    {
        try {
            try {
                $input = new ListDraftWikisInput(
                    statuses: array_map(
                        static fn (string $status): ApprovalStatus => ApprovalStatus::from($status),
                        $request->statuses(),
                    ),
                    translationSetIdentifier: $request->translationSetIdentifier() !== null ? new TranslationSetIdentifier($request->translationSetIdentifier()) : null,
                    resourceType: $request->resourceType() !== null ? ResourceType::from($request->resourceType()) : null,
                    principalIdentifier: $this->wikiContext->principalIdentifier,
                    perPage: $request->perPage(),
                );
                $output = new ListDraftWikisOutput();
                $this->listDraftWikis->process($input, $output);
            } catch (DisallowedException $e) {
                throw new ForbiddenHttpException(detail: error_message('disallowed', $request->language()), previous: $e);
            } catch (PrincipalNotFoundException $e) {
                throw new NotFoundHttpException(detail: error_message('principal_not_found', $request->language()), previous: $e);
            }
        } catch (NotFoundHttpException|ForbiddenHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
