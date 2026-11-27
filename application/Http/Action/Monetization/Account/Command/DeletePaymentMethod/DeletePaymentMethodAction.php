<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Account\Command\DeletePaymentMethod;

use Application\Http\Context\AccountContext;
use Application\Http\Context\ActorContext;
use Application\Http\Context\MonetizationContext;
use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\ServiceUnavailableHttpException;
use Application\Http\Exceptions\UnauthorizedHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Monetization\Account\Application\Exception\CardGatewayException;
use Source\Monetization\Account\Application\Exception\CardRegistrationRejectedException;
use Source\Monetization\Account\Application\Exception\PaymentMethodNotFoundException;
use Source\Monetization\Account\Application\UseCase\Command\DeletePaymentMethod\DeletePaymentMethodInput;
use Source\Monetization\Account\Application\UseCase\Command\DeletePaymentMethod\DeletePaymentMethodInterface;
use Source\Monetization\Account\Application\UseCase\Command\DeletePaymentMethod\DeletePaymentMethodOutput;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class DeletePaymentMethodAction
{
    public function __construct(
        private DeletePaymentMethodInterface $deletePaymentMethod,
        private AccountContext $accountContext,
        private MonetizationContext $monetizationContext,
        private ActorContext $actorContext,
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(DeletePaymentMethodRequest $request): JsonResponse
    {
        try {
            try {
                $input = new DeletePaymentMethodInput($this->accountContext->principal()->accountIdentifier(), new PaymentMethodIdentifier($request->paymentMethodIdentifier()), $this->monetizationContext->principalIdentifier);
                $output = new DeletePaymentMethodOutput();
                DB::transaction(fn () => $this->deletePaymentMethod->process($input, $output));
            } catch (UnauthorizedException $e) {
                throw new ForbiddenHttpException(detail: error_message('unauthorized', $this->actorContext->language->value), previous: $e);
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: error_message('invalid_payment_method', $this->actorContext->language->value), previous: $e);
            } catch (StepUpAuthenticationRequiredException $e) {
                throw new UnauthorizedHttpException(detail: error_message('recent_authentication_required', $this->actorContext->language->value), extensions: ['code' => 'recent_authentication_required'], previous: $e);
            } catch (CardGatewayException $e) {
                throw new ServiceUnavailableHttpException(previous: $e);
            } catch (CardRegistrationRejectedException $e) {
                throw new ConflictHttpException(detail: error_message('invalid_payment_method', $this->actorContext->language->value), extensions: ['code' => 'card_registration_rejected'], previous: $e);
            } catch (PaymentMethodNotFoundException $e) {
                throw new NotFoundHttpException(detail: error_message('payment_method_not_found', $this->actorContext->language->value), previous: $e);
            }
        } catch (ForbiddenHttpException|UnprocessableEntityHttpException|UnauthorizedHttpException|ConflictHttpException|NotFoundHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (ServiceUnavailableHttpException $e) {
            $this->logger->error('Card gateway operation failed.');

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
