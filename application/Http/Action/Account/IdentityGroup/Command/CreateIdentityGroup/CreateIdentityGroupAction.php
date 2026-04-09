<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\IdentityGroup\Command\CreateIdentityGroup;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\IdentityGroup\Application\UseCase\Command\CreateIdentityGroup\CreateIdentityGroupInput;
use Source\Account\IdentityGroup\Application\UseCase\Command\CreateIdentityGroup\CreateIdentityGroupInterface;
use Source\Account\IdentityGroup\Application\UseCase\Command\CreateIdentityGroup\CreateIdentityGroupOutput;
use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class CreateIdentityGroupAction
{
    public function __construct(
        private CreateIdentityGroupInterface $createIdentityGroup,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param CreateIdentityGroupRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(CreateIdentityGroupRequest $request): JsonResponse
    {
        try {
            try {
                $input = new CreateIdentityGroupInput(
                    accountIdentifier: new AccountIdentifier($request->accountIdentifier()),
                    name: $request->name(),
                    role: AccountRole::from($request->role()),
                );
                $output = new CreateIdentityGroupOutput();
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->createIdentityGroup->process($input, $output);
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
