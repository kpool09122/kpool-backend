<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\CreatePrincipal;

use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInput;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInterface;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalOutput;
use Source\Wiki\Principal\Domain\Exception\PrincipalAlreadyExistsException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class CreatePrincipalAction
{
    public function __construct(
        private CreatePrincipalInterface $createPrincipal,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(CreatePrincipalRequest $request): JsonResponse
    {
        try {
            try {
                $input = new CreatePrincipalInput(
                    new IdentityIdentifier($request->identityIdentifier()),
                    new AccountIdentifier($request->accountIdentifier()),
                );
                $output = new CreatePrincipalOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->createPrincipal->process($input, $output);
                DB::commit();
            } catch (PrincipalAlreadyExistsException $e) {
                DB::rollBack();

                throw new ConflictHttpException(detail: error_message('principal_already_exists', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (ConflictHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
