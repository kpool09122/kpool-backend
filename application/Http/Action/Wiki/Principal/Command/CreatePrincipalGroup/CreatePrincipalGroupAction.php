<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\CreatePrincipalGroup;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipalGroup\CreatePrincipalGroupInput;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipalGroup\CreatePrincipalGroupInterface;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipalGroup\CreatePrincipalGroupOutput;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class CreatePrincipalGroupAction
{
    public function __construct(
        private CreatePrincipalGroupInterface $createPrincipalGroup,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(CreatePrincipalGroupRequest $request): JsonResponse
    {
        try {
            try {
                $input = new CreatePrincipalGroupInput(
                    new AccountIdentifier($request->accountIdentifier()),
                    $request->name(),
                );
                $output = new CreatePrincipalGroupOutput();
            } catch (InvalidArgumentException $e) {
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
