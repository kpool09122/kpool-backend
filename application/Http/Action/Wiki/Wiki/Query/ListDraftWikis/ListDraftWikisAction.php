<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\ListDraftWikis;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInput;
use Source\Wiki\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
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
        private GetCurrentPrincipalInterface $getCurrentPrincipal,
        private ActorContext $actorContext,
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
                editorIdentifier: $request->onlyMine() ? $this->currentPrincipalIdentifier() : null,
                perPage: $request->perPage(),
            );
            $output = new ListDraftWikisOutput();
            $this->listDraftWikis->process($input, $output);
        } catch (NotFoundHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }

    private function currentPrincipalIdentifier(): PrincipalIdentifier
    {
        try {
            $principal = $this->getCurrentPrincipal->process(new GetCurrentPrincipalInput($this->actorContext->identityIdentifier));
        } catch (PrincipalNotFoundException $e) {
            throw new NotFoundHttpException(detail: error_message('principal_not_found', $this->actorContext->language->value), previous: $e);
        }

        /** @var array{principalIdentifier: string} $payload */
        $payload = $principal->toArray();

        return new PrincipalIdentifier($payload['principalIdentifier']);
    }
}
