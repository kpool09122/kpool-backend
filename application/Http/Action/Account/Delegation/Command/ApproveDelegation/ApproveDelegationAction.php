<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Delegation\Command\ApproveDelegation;

use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Delegation\Application\Exception\DelegationNotFoundException;
use Source\Account\Delegation\Application\Exception\DisallowedDelegationOperationException;
use Source\Account\Delegation\Application\UseCase\Command\ApproveDelegation\ApproveDelegationInput;
use Source\Account\Delegation\Application\UseCase\Command\ApproveDelegation\ApproveDelegationInterface;
use Source\Account\Delegation\Application\UseCase\Command\ApproveDelegation\ApproveDelegationOutput;
use Source\Account\Delegation\Domain\Exception\InvalidDelegationApprovalException;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ApproveDelegationAction
{
    public function __construct(
        private ApproveDelegationInterface $approveDelegation,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param ApproveDelegationRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(ApproveDelegationRequest $request): JsonResponse
    {
        try {
            try {
                $input = new ApproveDelegationInput(
                    delegationIdentifier: new DelegationIdentifier($request->delegationId()),
                    approverIdentifier: new IdentityIdentifier($request->approverIdentifier()),
                );
                $output = new ApproveDelegationOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->approveDelegation->process($input, $output);
                DB::commit();
            } catch (DelegationNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('delegation_not_found', $language), previous: $e);
            } catch (DisallowedDelegationOperationException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed_delegation_operation', $language), previous: $e);
            } catch (InvalidDelegationApprovalException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_delegation_approval', $language), previous: $e);
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

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
