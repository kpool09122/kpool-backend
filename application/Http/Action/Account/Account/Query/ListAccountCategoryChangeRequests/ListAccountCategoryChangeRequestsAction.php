<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Query\ListAccountCategoryChangeRequests;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\UseCase\Query\ListAccountCategoryChangeRequests\ListAccountCategoryChangeRequestsInput;
use Source\Account\Account\Application\UseCase\Query\ListAccountCategoryChangeRequests\ListAccountCategoryChangeRequestsInterface;
use Source\Account\Account\Application\UseCase\Query\ListAccountCategoryChangeRequests\ListAccountCategoryChangeRequestsOutput;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListAccountCategoryChangeRequestsAction
{
    public function __construct(
        private ListAccountCategoryChangeRequestsInterface $listAccountCategoryChangeRequests,
        private AccountContext $accountContext,
        // @phpstan-ignore property.onlyWritten
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ListAccountCategoryChangeRequestsRequest $request): JsonResponse
    {
        try {
            $language = $request->language();

            try {
                $input = new ListAccountCategoryChangeRequestsInput(
                    principal: $this->accountContext->principal(),
                    status: $request->status(),
                    requestedAccountCategory: $request->requestedAccountCategory(),
                    perPage: $request->perPage(),
                    page: $request->page(),
                );
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            try {
                $output = new ListAccountCategoryChangeRequestsOutput();
                $this->listAccountCategoryChangeRequests->process($input, $output);
            } catch (AccountCategoryChangeRequestForbiddenException $e) {
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
