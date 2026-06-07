<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\GetMyAgencyDraftWiki;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInput;
use Source\Wiki\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\GetMyAgencyDraftWiki\GetMyAgencyDraftWikiInput;
use Source\Wiki\Wiki\Application\UseCase\Query\GetMyAgencyDraftWiki\GetMyAgencyDraftWikiInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class GetMyAgencyDraftWikiAction
{
    public function __construct(
        private GetMyAgencyDraftWikiInterface $getMyAgencyDraftWiki,
        private GetCurrentPrincipalInterface $getCurrentPrincipal,
        private ActorContext $actorContext,
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(GetMyAgencyDraftWikiRequest $request): JsonResponse
    {
        try {
            try {
                $input = new GetMyAgencyDraftWikiInput(
                    new Slug($request->slug()),
                    Language::from($request->language()),
                    $this->currentPrincipalIdentifier(),
                );
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            try {
                $readModel = $this->getMyAgencyDraftWiki->process($input);
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
