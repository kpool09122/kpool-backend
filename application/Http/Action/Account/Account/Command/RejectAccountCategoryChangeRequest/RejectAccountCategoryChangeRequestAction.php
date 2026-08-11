<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\RejectAccountCategoryChangeRequest;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestNotFoundException;
use Source\Account\Account\Application\UseCase\Command\RejectAccountCategoryChangeRequest\RejectAccountCategoryChangeRequestInput;
use Source\Account\Account\Application\UseCase\Command\RejectAccountCategoryChangeRequest\RejectAccountCategoryChangeRequestInterface;
use Source\Account\Account\Application\UseCase\Command\RejectAccountCategoryChangeRequest\RejectAccountCategoryChangeRequestOutput;
use Source\Account\Account\Domain\Exception\InvalidAccountCategoryChangeRequestRejectionException;
use Source\Account\Account\Domain\ValueObject\AccountCategoryChangeRequestIdentifier;
use Source\Account\Account\Domain\ValueObject\RejectionReason;
use Source\Account\Account\Domain\ValueObject\RejectionReasonCode;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class RejectAccountCategoryChangeRequestAction
{
    public function __construct(private RejectAccountCategoryChangeRequestInterface $useCase, private AccountContext $accountContext, private LoggerInterface $logger)
    {
    }

    public function __invoke(RejectAccountCategoryChangeRequestRequest $request): JsonResponse
    {
        try {
            $language = $request->language();

            try {
                $input = new RejectAccountCategoryChangeRequestInput(
                    new AccountCategoryChangeRequestIdentifier($request->requestId()),
                    $this->accountContext->principal(),
                    new RejectionReason(RejectionReasonCode::from($request->rejectionReasonCode()), $request->rejectionReasonDetail()),
                );
                $output = new RejectAccountCategoryChangeRequestOutput();
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->useCase->process($input, $output);
                DB::commit();
            } catch (AccountCategoryChangeRequestNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: $e->getMessage(), previous: $e);
            } catch (AccountCategoryChangeRequestForbiddenException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed', $language), previous: $e);
            } catch (InvalidAccountCategoryChangeRequestRejectionException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (ForbiddenHttpException|UnprocessableEntityHttpException|NotFoundHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
