<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Query\GetAccountCategoryChangeRequest;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestNotFoundException;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\UseCase\Query\GetAccountCategoryChangeRequest\GetAccountCategoryChangeRequestInput;
use Source\Account\Account\Application\UseCase\Query\GetAccountCategoryChangeRequest\GetAccountCategoryChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Query\GetAccountCategoryChangeRequest\GetAccountCategoryChangeRequestOutput;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class GetAccountCategoryChangeRequestAction
{
    public function __construct(
        private GetAccountCategoryChangeRequestInterface $getAccountCategoryChangeRequest,
        private AccountContext $accountContext,
        // @phpstan-ignore property.onlyWritten
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(GetAccountCategoryChangeRequestRequest $request): JsonResponse
    {
        try {
            $language = $request->language();

            try {
                $input = new GetAccountCategoryChangeRequestInput(
                    requestIdentifier: new AccountCategoryChangeRequestIdentifier($request->requestId()),
                    principal: $this->accountContext->principal(),
                );
                $output = new GetAccountCategoryChangeRequestOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            try {
                $this->getAccountCategoryChangeRequest->process($input, $output);
            } catch (AccountCategoryChangeRequestNotFoundException|AccountNotFoundException $e) {
                throw new NotFoundHttpException(detail: $e->getMessage(), previous: $e);
            } catch (AccountCategoryChangeRequestForbiddenException $e) {
                throw new ForbiddenHttpException(detail: error_message('disallowed', $language), previous: $e);
            }
        } catch (ForbiddenHttpException|NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
