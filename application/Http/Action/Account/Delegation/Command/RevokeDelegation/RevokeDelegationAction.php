<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Delegation\Command\RevokeDelegation;

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
use Source\Account\Delegation\Application\UseCase\Command\RevokeDelegation\RevokeDelegationInput;
use Source\Account\Delegation\Application\UseCase\Command\RevokeDelegation\RevokeDelegationInterface;
use Source\Account\Delegation\Application\UseCase\Command\RevokeDelegation\RevokeDelegationOutput;
use Source\Account\Delegation\Domain\Exception\InvalidDelegationRevocationException;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RevokeDelegationAction
{
    public function __construct(
        private RevokeDelegationInterface $revokeDelegation,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param RevokeDelegationRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(RevokeDelegationRequest $request): JsonResponse
    {
        try {
            try {
                $input = new RevokeDelegationInput(
                    delegationIdentifier: new DelegationIdentifier($request->delegationId()),
                    revokerIdentifier: new IdentityIdentifier($request->revokerIdentifier()),
                );
                $output = new RevokeDelegationOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->revokeDelegation->process($input, $output);
                DB::commit();
            } catch (DelegationNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('delegation_not_found', $language), previous: $e);
            } catch (DisallowedDelegationOperationException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed_delegation_operation', $language), previous: $e);
            } catch (InvalidDelegationRevocationException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_delegation_revocation', $language), previous: $e);
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
