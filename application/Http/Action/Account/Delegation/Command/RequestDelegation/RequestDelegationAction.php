<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Delegation\Command\RequestDelegation;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Affiliation\Application\Exception\AffiliationNotFoundException;
use Source\Account\Affiliation\Application\Exception\InvalidAffiliationStatusException;
use Source\Account\Delegation\Application\UseCase\Command\RequestDelegation\RequestDelegationInput;
use Source\Account\Delegation\Application\UseCase\Command\RequestDelegation\RequestDelegationInterface;
use Source\Account\Delegation\Application\UseCase\Command\RequestDelegation\RequestDelegationOutput;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RequestDelegationAction
{
    public function __construct(
        private RequestDelegationInterface $requestDelegation,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param RequestDelegationRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(RequestDelegationRequest $request): JsonResponse
    {
        try {
            try {
                $input = new RequestDelegationInput(
                    affiliationIdentifier: new AffiliationIdentifier($request->affiliationIdentifier()),
                    delegateIdentifier: new IdentityIdentifier($request->delegateIdentifier()),
                    delegatorIdentifier: new IdentityIdentifier($request->delegatorIdentifier()),
                );
                $output = new RequestDelegationOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->requestDelegation->process($input, $output);
                DB::commit();
            } catch (AffiliationNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('affiliation_not_found', $language), previous: $e);
            } catch (InvalidAffiliationStatusException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_affiliation_status', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
