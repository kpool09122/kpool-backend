<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Account\Query\GetPaymentMethod;

use Application\Http\Context\AccountContext;
use Application\Http\Context\ActorContext;
use Application\Http\Context\MonetizationContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Monetization\Account\Application\Exception\PaymentMethodNotFoundException;
use Source\Monetization\Account\Application\UseCase\Query\GetPaymentMethod\GetPaymentMethodInput;
use Source\Monetization\Account\Application\UseCase\Query\GetPaymentMethod\GetPaymentMethodInterface;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class GetPaymentMethodAction
{
    public function __construct(
        private GetPaymentMethodInterface $getPaymentMethod,
        private AccountContext $accountContext,
        private MonetizationContext $monetizationContext,
        private ActorContext $actorContext,
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(GetPaymentMethodRequest $request): JsonResponse
    {
        try {
            try {
                $input = new GetPaymentMethodInput($this->accountContext->principal()->accountIdentifier(), new PaymentMethodIdentifier($request->paymentMethodIdentifier()), $this->monetizationContext->principalIdentifier);
                $result = $this->getPaymentMethod->process($input);
            } catch (UnauthorizedException $e) {
                throw new ForbiddenHttpException(detail: error_message('unauthorized', $this->actorContext->language->value), previous: $e);
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: error_message('invalid_payment_method', $this->actorContext->language->value), previous: $e);
            } catch (PaymentMethodNotFoundException $e) {
                throw new NotFoundHttpException(detail: error_message('payment_method_not_found', $this->actorContext->language->value), previous: $e);
            }
        } catch (ForbiddenHttpException|UnprocessableEntityHttpException|NotFoundHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json($result->toArray(), Response::HTTP_OK);
    }
}
