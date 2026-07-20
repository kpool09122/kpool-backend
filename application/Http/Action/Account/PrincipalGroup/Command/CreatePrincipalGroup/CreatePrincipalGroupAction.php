<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\PrincipalGroup\Command\CreatePrincipalGroup;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Principal\Application\UseCase\Command\CreatePrincipalGroup\CreatePrincipalGroupInput;
use Source\Account\Principal\Application\UseCase\Command\CreatePrincipalGroup\CreatePrincipalGroupInterface;
use Source\Account\Principal\Application\UseCase\Command\CreatePrincipalGroup\CreatePrincipalGroupOutput;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class CreatePrincipalGroupAction
{
    public function __construct(
        private CreatePrincipalGroupInterface $createPrincipalGroup,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param CreatePrincipalGroupRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(CreatePrincipalGroupRequest $request): JsonResponse
    {
        try {
            try {
                $input = new CreatePrincipalGroupInput(
                    accountIdentifier: new AccountIdentifier($request->accountIdentifier()),
                    name: $request->name(),
                    role: AccountRole::from($request->role()),
                );
                $output = new CreatePrincipalGroupOutput();
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->createPrincipalGroup->process($input, $output);
                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
