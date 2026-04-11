<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\CreateRole;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Principal\Application\UseCase\Command\CreateRole\CreateRoleInput;
use Source\Wiki\Principal\Application\UseCase\Command\CreateRole\CreateRoleInterface;
use Source\Wiki\Principal\Application\UseCase\Command\CreateRole\CreateRoleOutput;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class CreateRoleAction
{
    public function __construct(
        private CreateRoleInterface $createRole,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(CreateRoleRequest $request): JsonResponse
    {
        try {
            try {
                $policies = array_map(
                    static fn (string $id) => new PolicyIdentifier($id),
                    $request->policies() ?? [],
                );

                $input = new CreateRoleInput(
                    $request->name(),
                    $policies,
                    $request->isSystemRole(),
                );
                $output = new CreateRoleOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->createRole->process($input, $output);
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
