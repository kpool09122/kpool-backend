<?php

declare(strict_types=1);

namespace Application\Http\Action\Monetization\Account\Command\PrepareCardRegistration;

use Application\Http\Context\AccountContext;
use Application\Http\Context\ActorContext;
use Application\Http\Context\MonetizationContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\ServiceUnavailableHttpException;
use Application\Http\Exceptions\UnauthorizedHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Monetization\Account\Application\Exception\CardGatewayException;
use Source\Monetization\Account\Application\UseCase\Command\PrepareCardRegistration\PrepareCardRegistrationInput;
use Source\Monetization\Account\Application\UseCase\Command\PrepareCardRegistration\PrepareCardRegistrationInterface;
use Source\Monetization\Account\Application\UseCase\Command\PrepareCardRegistration\PrepareCardRegistrationOutput;
use Source\Monetization\Account\Domain\Exception\PaymentCustomerAlreadyLinkedException;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class PrepareCardRegistrationAction
{
    public function __construct(
        private PrepareCardRegistrationInterface $prepareCardRegistration,
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
                $input = new PrepareCardRegistrationInput($this->accountContext->principal()->accountIdentifier(), $this->monetizationContext->principalIdentifier);
                $output = new PrepareCardRegistrationOutput();
                DB::transaction(fn () => $this->prepareCardRegistration->process($input, $output));
            } catch (UnauthorizedException $e) {
                throw new ForbiddenHttpException(detail: error_message('unauthorized', $this->actorContext->language->value), previous: $e);
            } catch (StepUpAuthenticationRequiredException $e) {
                throw new UnauthorizedHttpException(detail: error_message('recent_authentication_required', $this->actorContext->language->value), extensions: ['code' => 'recent_authentication_required'], previous: $e);
            } catch (CardGatewayException $e) {
                throw new ServiceUnavailableHttpException(previous: $e);
            } catch (PaymentCustomerAlreadyLinkedException $e) {
                throw new ServiceUnavailableHttpException(previous: $e);
            }
        } catch (ForbiddenHttpException|UnauthorizedHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (ServiceUnavailableHttpException $e) {
            $this->logger->error('Card gateway operation failed.');

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED)->header('Cache-Control', 'no-store');
    }
}
