<?php

declare(strict_types=1);

namespace Application\Http\Action\Analyze\Principal\Command\CreatePrincipal;

use Application\Http\Context\AccountContext;
use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Source\Analyze\Principal\Application\Exception\AccountMembershipRequiredException;
use Source\Analyze\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInput;
use Source\Analyze\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInterface;
use Source\Analyze\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalOutput;
use Source\Analyze\Principal\Domain\Exception\PrincipalAlreadyExistsException;
use Throwable;

readonly class CreatePrincipalAction
{
    public function __construct(private CreatePrincipalInterface $createPrincipal, private ActorContext $actorContext, private AccountContext $accountContext, private LoggerInterface $logger)
    {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(): JsonResponse
    {
        try {
            try {
                $input = new CreatePrincipalInput($this->actorContext->identityIdentifier, $this->accountContext->principal()->accountIdentifier());
                $output = new CreatePrincipalOutput();
                DB::transaction(fn () => $this->createPrincipal->process($input, $output));
            } catch (AccountMembershipRequiredException $e) {
                throw new ForbiddenHttpException(detail: error_message('unauthorized', $this->actorContext->language->value), previous: $e);
            } catch (PrincipalAlreadyExistsException $e) {
                throw new ConflictHttpException(detail: error_message('principal_already_exists', $this->actorContext->language->value), previous: $e);
            }
        } catch (ConflictHttpException|ForbiddenHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json($output->toArray(), 201);
    }
}
