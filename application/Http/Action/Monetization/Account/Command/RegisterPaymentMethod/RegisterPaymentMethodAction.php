<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Account\Command\RegisterPaymentMethod;

use Application\Http\Context\AccountContext;
use Application\Http\Context\ActorContext;
use Application\Http\Context\MonetizationContext;
use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
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
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodInput;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodInterface;
use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodOutput;
use Source\Monetization\Account\Domain\ValueObject\SetupIntentIdentifier;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RegisterPaymentMethodAction
{
    public function __construct(
        private RegisterPaymentMethodInterface $registerPaymentMethod,
        private AccountContext $accountContext,
        private MonetizationContext $monetizationContext,
        private ActorContext $actorContext,
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(RegisterPaymentMethodRequest $request): JsonResponse
    {
        try {
            try {
                $input = new RegisterPaymentMethodInput($this->accountContext->principal()->accountIdentifier(), new SetupIntentIdentifier($request->setupIntentIdentifier()), $this->monetizationContext->principalIdentifier);
                $output = new RegisterPaymentMethodOutput();
                DB::transaction(fn () => $this->registerPaymentMethod->process($input, $output));
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
            }
        } catch (ForbiddenHttpException|UnprocessableEntityHttpException|UnauthorizedHttpException|ConflictHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (ServiceUnavailableHttpException $e) {
            $this->logger->error('Card gateway operation failed.');

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
