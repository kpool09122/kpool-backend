<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Wiki\Query\ListRelatedWikis;

use Application\Http\Context\AccountContext;
use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedWikis\ListRelatedWikisInput;
use Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedWikis\ListRelatedWikisInterface;
use Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedWikis\ListRelatedWikisOutput;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class ListRelatedWikisAction
{
    public function __construct(
        private ListRelatedWikisInterface $listRelatedWikis,
        private WikiContext $wikiContext,
        private AccountContext $accountContext,
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(ListRelatedWikisRequest $request): JsonResponse
    {
        try {
            try {
                $input = new ListRelatedWikisInput(
                    resourceType: ResourceType::from($request->resourceType()),
                    translationSetIdentifier: new TranslationSetIdentifier($request->translationSetIdentifier()),
                    principalIdentifier: $this->wikiContext->principalIdentifier,
                    accountCategory: $this->accountContext->accountCategory(),
                );
                $output = new ListRelatedWikisOutput();
                $this->listRelatedWikis->process($input, $output);
            } catch (DisallowedException $e) {
                throw new ForbiddenHttpException(detail: error_message('disallowed', $request->language()), previous: $e);
            } catch (PrincipalNotFoundException|WikiNotFoundException $e) {
                throw new NotFoundHttpException(detail: 'Wiki not found.', previous: $e);
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }
        } catch (NotFoundHttpException|ForbiddenHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
