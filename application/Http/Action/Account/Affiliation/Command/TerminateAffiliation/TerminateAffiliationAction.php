<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Affiliation\Command\TerminateAffiliation;

use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Affiliation\Application\Exception\AffiliationNotFoundException;
use Source\Account\Affiliation\Application\Exception\DisallowedAffiliationOperationException;
use Source\Account\Affiliation\Application\UseCase\Command\TerminateAffiliation\TerminateAffiliationInput;
use Source\Account\Affiliation\Application\UseCase\Command\TerminateAffiliation\TerminateAffiliationInterface;
use Source\Account\Affiliation\Application\UseCase\Command\TerminateAffiliation\TerminateAffiliationOutput;
use Source\Account\Affiliation\Domain\Exception\InvalidAffiliationTerminationException;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class TerminateAffiliationAction
{
    public function __construct(
        private TerminateAffiliationInterface $terminateAffiliation,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(TerminateAffiliationRequest $request): JsonResponse
    {
        try {
            try {
                $input = new TerminateAffiliationInput(
                    affiliationIdentifier: new AffiliationIdentifier($request->affiliationId()),
                    terminatorAccountIdentifier: new AccountIdentifier($request->terminatorAccountIdentifier()),
                );
                $output = new TerminateAffiliationOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->terminateAffiliation->process($input, $output);
                DB::commit();
            } catch (AffiliationNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('affiliation_not_found', $language), previous: $e);
            } catch (DisallowedAffiliationOperationException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed_affiliation_operation', $language), previous: $e);
            } catch (InvalidAffiliationTerminationException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_affiliation_termination', $language), previous: $e);
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
