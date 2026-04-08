<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Account\Command\OnboardSeller;

use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Monetization\Account\Application\UseCase\Command\OnboardSeller\OnboardSellerInput;
use Source\Monetization\Account\Application\UseCase\Command\OnboardSeller\OnboardSellerInterface;
use Source\Monetization\Account\Application\UseCase\Command\OnboardSeller\OnboardSellerOutput;
use Source\Monetization\Account\Domain\Exception\CapabilityAlreadyGrantedException;
use Source\Monetization\Account\Domain\Exception\MonetizationAccountNotFoundException;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Infrastructure\Exception\StripeConnectException;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Email;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class OnboardSellerAction
{
    public function __construct(
        private OnboardSellerInterface $onboardSeller,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param OnboardSellerRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(OnboardSellerRequest $request): JsonResponse
    {
        try {
            try {
                $input = new OnboardSellerInput(
                    new MonetizationAccountIdentifier($request->monetizationAccountId()),
                    new Email($request->email()),
                    CountryCode::from($request->countryCode()),
                    $request->refreshUrl(),
                    $request->returnUrl(),
                );
                $output = new OnboardSellerOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->onboardSeller->process($input, $output);
                DB::commit();
            } catch (MonetizationAccountNotFoundException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new NotFoundHttpException(detail: error_message('monetization_account_not_found', $language), previous: $e);
            } catch (CapabilityAlreadyGrantedException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new ConflictHttpException(detail: error_message('capability_already_granted', $language), previous: $e);
            } catch (StripeConnectException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new InternalServerErrorHttpException(detail: error_message('stripe_connect_error', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw $e;
            }
        } catch (NotFoundHttpException|UnprocessableEntityHttpException|ConflictHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
