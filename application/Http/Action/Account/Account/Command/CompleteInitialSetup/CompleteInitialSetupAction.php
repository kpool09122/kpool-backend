<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\Account\Command\CompleteInitialSetup;

use Application\Http\Context\AccountResolver;
use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Service\AccountContextInvalidationServiceInterface;
use Source\Account\Account\Application\UseCase\Command\CompleteInitialSetup\CompleteInitialSetupInput;
use Source\Account\Account\Application\UseCase\Command\CompleteInitialSetup\CompleteInitialSetupInterface;
use Source\Account\Account\Domain\Exception\AccountSetupUnavailableException;
use Source\Account\Delegation\Application\Exception\DelegationUnavailableException;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class CompleteInitialSetupAction
{
    public function __construct(
        private CompleteInitialSetupInterface $completeInitialSetup,
        private AccountResolver $accountResolver,
        private ActorContext $actorContext,
        private AccountContextInvalidationServiceInterface $accountContextInvalidationService,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(CompleteInitialSetupRequest $request): Response
    {
        try {
            try {
                $context = $this->accountResolver->resolve($this->actorContext->identityIdentifier);
                $input = new CompleteInitialSetupInput(
                    $context->originalAccountIdentifier(),
                    AccountType::from($request->accountType()),
                );
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            } catch (AccountNotFoundException $e) {
                throw new NotFoundHttpException(detail: 'Account not found.', previous: $e);
            } catch (DelegationUnavailableException $e) {
                throw new ForbiddenHttpException(detail: 'The selected account is unavailable.', previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->completeInitialSetup->process($input);
                DB::commit();
            } catch (AccountSetupUnavailableException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(
                    detail: 'This account cannot complete initial setup.',
                    extensions: ['code' => 'account_setup_unavailable'],
                    previous: $e,
                );
            } catch (AccountNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: 'Account not found.', previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }

            $this->accountContextInvalidationService->forgetByAccountIdentifier($input->accountIdentifier());
        } catch (ForbiddenHttpException|NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->noContent();
    }
}
