<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Affiliation\Query\ListAffiliations;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Affiliation\Application\Exception\DisallowedAffiliationOperationException;
use Source\Account\Affiliation\Application\UseCase\Query\ListAffiliations\ListAffiliationsInput;
use Source\Account\Affiliation\Application\UseCase\Query\ListAffiliations\ListAffiliationsInterface;
use Source\Account\Affiliation\Application\UseCase\Query\ListAffiliations\ListAffiliationsOutput;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListAffiliationsAction
{
    public function __construct(
        private ListAffiliationsInterface $listAffiliations,
        private AccountContext $accountContext,
        // @phpstan-ignore property.onlyWritten
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ListAffiliationsRequest $request): JsonResponse
    {
        try {
            $language = $request->language();

            try {
                $input = new ListAffiliationsInput(
                    principal: $this->accountContext->principal(),
                    status: $request->status(),
                    viewerRole: $request->viewerRole(),
                    perPage: $request->perPage(),
                    page: $request->page(),
                );
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            try {
                $output = new ListAffiliationsOutput();
                $this->listAffiliations->process($input, $output);
            } catch (DisallowedAffiliationOperationException $e) {
                throw new ForbiddenHttpException(detail: error_message('disallowed', $language), previous: $e);
            }
        } catch (ForbiddenHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
