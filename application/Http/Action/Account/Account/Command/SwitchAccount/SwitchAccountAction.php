<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\SwitchAccount;

use Application\Http\Context\AccountContext;
use Application\Http\Context\ActorContext;
use Application\Http\Context\AuthContextCache;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\UseCase\Command\SwitchAccount\SwitchAccountInput;
use Source\Account\Account\Application\UseCase\Command\SwitchAccount\SwitchAccountInterface;
use Source\Account\Account\Application\UseCase\Command\SwitchAccount\SwitchAccountOutput;
use Source\Account\Delegation\Application\Exception\DelegationNotFoundException;
use Source\Account\Delegation\Application\Exception\DisallowedDelegationOperationException;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class SwitchAccountAction
{
    public function __construct(
        private SwitchAccountInterface $switchAccount,
        private ActorContext $actorContext,
        private AccountContext $accountContext,
        private AuthContextCache $cache,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SwitchAccountRequest $request): JsonResponse
    {
        try {
            try {
                $delegationIdentifier = $request->delegationIdentifier();
                $input = new SwitchAccountInput(
                    identityIdentifier: $this->actorContext->identityIdentifier,
                    originalPrincipalIdentifier: $this->accountContext->originalPrincipalIdentifier(),
                    targetDelegationIdentifier: $delegationIdentifier !== null
                        ? new DelegationIdentifier($delegationIdentifier)
                        : null,
                );
                $output = new SwitchAccountOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }
            DB::beginTransaction();
            $language = $request->language();

            try {
                $this->switchAccount->process($input, $output);
                DB::commit();
            } catch (DelegationNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('delegation_not_found', $language), previous: $e);
            } catch (DisallowedDelegationOperationException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed_delegation_operation', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (ForbiddenHttpException|NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        $this->cache->forgetAccount($this->actorContext->identityIdentifier);
        $this->cache->forgetWiki($this->actorContext->identityIdentifier);

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
