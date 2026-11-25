<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Account\Command\RegisterPaymentMethod;

use Application\Http\Context\MonetizationContext;
use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Monetization\Account\Application\Exception\PaymentMethodAlreadyExistsException;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodInput;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodInterface;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodOutput;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RegisterPaymentMethodAction
{
    public function __construct(
        private RegisterPaymentMethodInterface $registerPaymentMethod,
        private LoggerInterface $logger,
        private MonetizationContext $monetizationContext,
    ) {
    }

    /**
     * @param RegisterPaymentMethodRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(RegisterPaymentMethodRequest $request): JsonResponse
    {
        try {
            try {
                $input = new RegisterPaymentMethodInput(
                    new MonetizationAccountIdentifier($request->monetizationAccountId()),
                    new ExternalPaymentMethodId($request->externalPaymentMethodId()),
                    PaymentMethodType::from($request->type()),
                    $this->monetizationContext->principalIdentifier,
                );
                $output = new RegisterPaymentMethodOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->registerPaymentMethod->process($input, $output);
                DB::commit();
            } catch (UnauthorizedException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('unauthorized', $request->language()), previous: $e);
            } catch (PaymentMethodAlreadyExistsException $e) {
                DB::rollBack();

                throw new ConflictHttpException(detail: error_message('payment_method_already_exists', $request->language()), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (UnprocessableEntityHttpException|ConflictHttpException|ForbiddenHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
