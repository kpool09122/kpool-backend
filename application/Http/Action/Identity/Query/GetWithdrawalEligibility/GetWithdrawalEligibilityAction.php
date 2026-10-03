<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Query\GetWithdrawalEligibility;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Service\WithdrawalEligibilityServiceInterface;
use Throwable;

readonly class GetWithdrawalEligibilityAction
{
    public function __construct(
        private WithdrawalEligibilityServiceInterface $withdrawalEligibilityService,
        private ActorContext $actorContext,
        // @phpstan-ignore property.onlyWritten
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(): JsonResponse
    {
        try {
            return response()->json([
                'canWithdraw' => $this->withdrawalEligibilityService->canWithdraw($this->actorContext->identityIdentifier),
            ]);
        } catch (Throwable $exception) {
            $this->logger->error((string) $exception);

            throw new InternalServerErrorHttpException(detail: error_message('internal_server_error', $this->actorContext->language->value), previous: $exception);
        }
    }
}
