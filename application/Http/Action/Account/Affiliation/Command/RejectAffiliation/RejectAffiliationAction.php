<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Affiliation\Command\RejectAffiliation;

use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Affiliation\Application\Exception\AffiliationNotFoundException;
use Source\Account\Affiliation\Application\Exception\DisallowedAffiliationOperationException;
use Source\Account\Affiliation\Application\UseCase\Command\RejectAffiliation\RejectAffiliationInput;
use Source\Account\Affiliation\Application\UseCase\Command\RejectAffiliation\RejectAffiliationInterface;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RejectAffiliationAction
{
    public function __construct(
        private RejectAffiliationInterface $rejectAffiliation,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(RejectAffiliationRequest $request): Response
    {
        try {
            try {
                $input = new RejectAffiliationInput(
                    affiliationIdentifier: new AffiliationIdentifier($request->affiliationId()),
                    rejectorAccountIdentifier: new AccountIdentifier($request->rejectorAccountIdentifier()),
                );
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->rejectAffiliation->process($input);
                DB::commit();
            } catch (AffiliationNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('affiliation_not_found', $language), previous: $e);
            } catch (DisallowedAffiliationOperationException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed_affiliation_operation', $language), previous: $e);
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
