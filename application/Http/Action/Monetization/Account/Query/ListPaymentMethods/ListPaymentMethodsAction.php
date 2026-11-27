<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Account\Query\ListPaymentMethods;

use Application\Http\Context\AccountContext;
use Application\Http\Context\ActorContext;
use Application\Http\Context\MonetizationContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Monetization\Account\Application\UseCase\Query\ListPaymentMethods\ListPaymentMethodsInput;
use Source\Monetization\Account\Application\UseCase\Query\ListPaymentMethods\ListPaymentMethodsInterface;
use Source\Monetization\Account\Application\UseCase\Query\PaymentMethodReadModel;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListPaymentMethodsAction
{
    public function __construct(
        private ListPaymentMethodsInterface $listPaymentMethods,
        private AccountContext $accountContext,
        private MonetizationContext $monetizationContext,
        private ActorContext $actorContext,
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(): JsonResponse
    {
        try {
            try {
                $input = new ListPaymentMethodsInput($this->accountContext->principal()->accountIdentifier(), $this->monetizationContext->principalIdentifier);
                $result = $this->listPaymentMethods->process($input);
            } catch (UnauthorizedException $e) {
                throw new ForbiddenHttpException(detail: error_message('unauthorized', $this->actorContext->language->value), previous: $e);
            }
        } catch (ForbiddenHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json(['paymentMethods' => array_map(static fn (PaymentMethodReadModel $row): array => $row->toArray(), $result)], Response::HTTP_OK);
    }
}
