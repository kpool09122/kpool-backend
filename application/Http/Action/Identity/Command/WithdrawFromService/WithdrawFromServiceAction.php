<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\WithdrawFromService;

use Application\Http\Context\ActorContext;
use Application\Http\Context\ServiceWithdrawalContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnauthorizedHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Domain\Exception\IdentityWithdrawalNotAllowedException;
use Source\Identity\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInput;
use Source\Identity\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInterface;
use Source\Identity\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceOutput;
use Source\Identity\Domain\Exception\IdentityNameConfirmationMismatchException;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class WithdrawFromServiceAction
{
    public function __construct(
        private WithdrawFromServiceInterface $withdrawFromService,
        private ActorContext $actorContext,
        private LoggerInterface $logger,
        private Request $request,
    ) {
    }

    public function __invoke(WithdrawFromServiceRequest $request): Response
    {
        try {
            $input = new WithdrawFromServiceInput($this->actorContext->identityIdentifier, $request->confirmationIdentityName());
            $output = new WithdrawFromServiceOutput();
            DB::transaction(function () use ($input, $output): void {
                $this->withdrawFromService->process($input, $output);
                DB::afterCommit(function (): void {
                    ServiceWithdrawalContext::markCommitted($this->request);
                });
            });
        } catch (IdentityNameConfirmationMismatchException $exception) {
            $problem = new UnprocessableEntityHttpException(
                detail: error_message('identity_name_confirmation_mismatch', $this->actorContext->language->value),
                extensions: ['code' => 'identity_name_confirmation_mismatch'],
                previous: $exception,
            );

            return response()->json($problem->toProblemDetails(), $problem->getHttpStatus());
        } catch (StepUpAuthenticationRequiredException $exception) {
            $problem = new UnauthorizedHttpException(
                detail: error_message('recent_authentication_required', $this->actorContext->language->value),
                extensions: ['code' => 'recent_authentication_required'],
                previous: $exception,
            );

            return response()->json($problem->toProblemDetails(), $problem->getHttpStatus());
        } catch (IdentityNotFoundException $exception) {
            $problem = new UnauthorizedHttpException(
                detail: error_message('unauthorized', $this->actorContext->language->value),
                extensions: ['code' => 'authentication_required'],
                previous: $exception,
            );

            return response()->json($problem->toProblemDetails(), $problem->getHttpStatus());
        } catch (IdentityWithdrawalNotAllowedException $exception) {
            $problem = new ForbiddenHttpException(
                detail: error_message('identity_withdrawal_not_allowed', $this->actorContext->language->value),
                extensions: ['code' => 'identity_withdrawal_not_allowed'],
                previous: $exception,
            );

            return response()->json($problem->toProblemDetails(), $problem->getHttpStatus());
        } catch (Throwable $exception) {
            $this->logger->error((string) $exception);

            throw new InternalServerErrorHttpException(detail: error_message('internal_server_error', $this->actorContext->language->value), previous: $exception);
        }

        return response()->noContent();
    }
}
