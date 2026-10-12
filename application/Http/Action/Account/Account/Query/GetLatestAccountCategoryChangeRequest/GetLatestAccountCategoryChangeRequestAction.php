<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Query\GetLatestAccountCategoryChangeRequest;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\UseCase\Query\GetLatestAccountCategoryChangeRequest\GetLatestAccountCategoryChangeRequestInput;
use Source\Account\Account\Application\UseCase\Query\GetLatestAccountCategoryChangeRequest\GetLatestAccountCategoryChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Query\GetLatestAccountCategoryChangeRequest\GetLatestAccountCategoryChangeRequestOutput;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class GetLatestAccountCategoryChangeRequestAction
{
    public function __construct(
        private GetLatestAccountCategoryChangeRequestInterface $getLatestAccountCategoryChangeRequest,
        private AccountContext $accountContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param GetLatestAccountCategoryChangeRequestRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(GetLatestAccountCategoryChangeRequestRequest $request): JsonResponse
    {
        try {
            $language = $request->language();

            try {
                $input = new GetLatestAccountCategoryChangeRequestInput(
                    accountIdentifier: new AccountIdentifier($request->accountIdentifier()),
                    principal: $this->accountContext->principal(),
                );
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            try {
                $output = new GetLatestAccountCategoryChangeRequestOutput();
                $this->getLatestAccountCategoryChangeRequest->process($input, $output);
            } catch (AccountCategoryChangeRequestForbiddenException $e) {
                throw new ForbiddenHttpException(detail: error_message('disallowed', $language), previous: $e);
            }
        } catch (ForbiddenHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
