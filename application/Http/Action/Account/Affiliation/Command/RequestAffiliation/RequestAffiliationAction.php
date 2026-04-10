<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Affiliation\Command\RequestAffiliation;

use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Affiliation\Application\Exception\AffiliationAlreadyExistsException;
use Source\Account\Affiliation\Application\Exception\InvalidAccountCategoryException;
use Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation\RequestAffiliationInput;
use Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation\RequestAffiliationInterface;
use Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation\RequestAffiliationOutput;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationTerms;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RequestAffiliationAction
{
    public function __construct(
        private RequestAffiliationInterface $requestAffiliation,
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
                    agencyAccountIdentifier: new AccountIdentifier($request->agencyAccountIdentifier()),
                    talentAccountIdentifier: new AccountIdentifier($request->talentAccountIdentifier()),
                    requestedBy: new AccountIdentifier($request->requestedBy()),
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
            } catch (AccountNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('account_not_found', $language), previous: $e);
            } catch (InvalidAccountCategoryException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_account_category_for_affiliation', $language), previous: $e);
            } catch (AffiliationAlreadyExistsException $e) {
                DB::rollBack();

                throw new ConflictHttpException(detail: error_message('affiliation_already_exists', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (ConflictHttpException|NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
