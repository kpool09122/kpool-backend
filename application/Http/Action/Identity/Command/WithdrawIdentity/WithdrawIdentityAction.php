<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\WithdrawIdentity;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnauthorizedHttpException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Domain\Exception\IdentityWithdrawalNotAllowedException;
use Source\Identity\Application\UseCase\Command\WithdrawIdentity\WithdrawIdentityInterface;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class WithdrawIdentityAction
{
    public const string COMMITTED_ATTRIBUTE = '_identity_withdrawal_committed';

    public function __construct(
        private WithdrawIdentityInterface $withdrawIdentity,
        private ActorContext $actorContext,
        private AuthServiceInterface $authService,
        private LoggerInterface $logger,
        private Request $request,
    ) {
    }

    public function __invoke(): Response
    {
        try {
            DB::transaction(function (): void {
                $this->withdrawIdentity->process($this->actorContext->identityIdentifier);
                DB::afterCommit(function (): void {
                    $this->request->attributes->set(self::COMMITTED_ATTRIBUTE, true);

                    try {
                        $this->authService->invalidateAllSessions($this->actorContext->identityIdentifier);
                    } catch (Throwable $exception) {
                        $this->logger->error('Withdrawn identity session invalidation failed.', ['exception' => $exception]);
                    }

                    try {
                        $this->authService->logout();
                    } catch (Throwable $exception) {
                        $this->logger->error('Withdrawn identity logout failed.', ['exception' => $exception]);
                    }
                });
            });
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
