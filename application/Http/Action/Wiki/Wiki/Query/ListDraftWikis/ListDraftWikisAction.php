<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\ListDraftWikis;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
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
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(ListDraftWikisRequest $request): JsonResponse
    {
        try {
            $input = new ListDraftWikisInput(
                status: ApprovalStatus::from($request->status()),
                translationSetIdentifier: $request->translationSetIdentifier() !== null ? new TranslationSetIdentifier($request->translationSetIdentifier()) : null,
                resourceType: $request->resourceType() !== null ? ResourceType::from($request->resourceType()) : null,
                perPage: $request->perPage(),
            );
            $output = new ListDraftWikisOutput();
            $this->listDraftWikis->process($input, $output);
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
