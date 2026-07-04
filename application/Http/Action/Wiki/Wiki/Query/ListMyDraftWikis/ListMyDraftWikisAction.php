<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\ListMyDraftWikis;

use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\UseCase\Query\ListMyDraftWikis\ListMyDraftWikisInput;
use Source\Wiki\Wiki\Application\UseCase\Query\ListMyDraftWikis\ListMyDraftWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListMyDraftWikis\ListMyDraftWikisOutput;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListMyDraftWikisAction
{
    public function __construct(
        private ListMyDraftWikisInterface $listMyDraftWikis,
        private WikiContext $wikiContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(ListMyDraftWikisRequest $request): JsonResponse
    {
        try {
            try {
                $input = new ListMyDraftWikisInput(
                    statuses: array_map(
                        static fn (string $status): ApprovalStatus => ApprovalStatus::from($status),
                        $request->statuses(),
                    ),
                    editorIdentifier: $this->wikiContext->principalIdentifier,
                    translationSetIdentifier: $request->translationSetIdentifier() !== null ? new TranslationSetIdentifier($request->translationSetIdentifier()) : null,
                    resourceType: $request->resourceType() !== null ? ResourceType::from($request->resourceType()) : null,
                    perPage: $request->perPage(),
                );
                $output = new ListMyDraftWikisOutput();
                $this->listMyDraftWikis->process($input, $output);
            } catch (DisallowedException $e) {
                throw new ForbiddenHttpException(detail: error_message('disallowed', $request->language()), previous: $e);
            }
        } catch (ForbiddenHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
