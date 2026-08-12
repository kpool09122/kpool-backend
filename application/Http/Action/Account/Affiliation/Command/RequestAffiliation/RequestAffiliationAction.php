<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Affiliation\Command\RequestAffiliation;

use Application\Http\Context\AccountContext;
use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Affiliation\Application\Exception\AffiliationAlreadyExistsException;
use Source\Account\Affiliation\Application\Exception\DisallowedAffiliationOperationException;
use Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation\RequestAffiliationInput;
use Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation\RequestAffiliationInterface;
use Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation\RequestAffiliationOutput;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationTerms;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\Email;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RequestAffiliationAction
{
    public function __construct(
        private RequestAffiliationInterface $requestAffiliation,
        private AccountContext $accountContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(RequestAffiliationRequest $request): JsonResponse
    {
        try {
            try {
                $terms = $request->terms();
                $input = new RequestAffiliationInput(
                    principal: $this->accountContext->principal(),
                    targetEmail: new Email($request->targetEmail()),
                    terms: $terms === null
                        ? null
                        : new AffiliationTerms(
                            isset($terms['revenueSharePercentage']) ? new Percentage((int) $terms['revenueSharePercentage']) : null,
                            isset($terms['contractNotes']) ? (string) $terms['contractNotes'] : null,
                        ),
                );
                $output = new RequestAffiliationOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->requestAffiliation->process($input, $output);
                DB::commit();
            } catch (DisallowedAffiliationOperationException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('disallowed_affiliation_operation', $language), previous: $e);
            } catch (AffiliationAlreadyExistsException $e) {
                DB::rollBack();

                throw new ConflictHttpException(detail: error_message('affiliation_already_exists', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (ConflictHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
