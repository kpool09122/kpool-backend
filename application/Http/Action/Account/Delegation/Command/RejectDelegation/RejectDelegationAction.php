<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Delegation\Command\RejectDelegation;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Delegation\Application\Exception\DelegationNotFoundException;
use Source\Account\Delegation\Application\Exception\DisallowedDelegationOperationException;
use Source\Account\Delegation\Application\UseCase\Command\RejectDelegation\RejectDelegationInput;
use Source\Account\Delegation\Application\UseCase\Command\RejectDelegation\RejectDelegationInterface;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RejectDelegationAction
{
    public function __construct(private RejectDelegationInterface $rejectDelegation, private AccountContext $accountContext, private LoggerInterface $logger)
    {
    }

    public function __invoke(RejectDelegationRequest $request): Response
    {
        try {
            try {
                $input = new RejectDelegationInput(new DelegationIdentifier($request->delegationId()), $this->accountContext->principal());
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }
            DB::beginTransaction();
            $language = $request->language();

            try {
                $this->rejectDelegation->process($input);
                DB::commit();
            } catch (DelegationNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('delegation_not_found', $language), previous: $e);
            } catch (DisallowedDelegationOperationException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed_delegation_operation', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (ForbiddenHttpException|NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->noContent(Response::HTTP_NO_CONTENT);
    }
}
