<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\DeleteRole;

use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Principal\Application\Exception\CannotDeleteSystemRoleException;
use Source\Wiki\Principal\Application\Exception\RoleNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\DeleteRole\DeleteRoleInput;
use Source\Wiki\Principal\Application\UseCase\Command\DeleteRole\DeleteRoleInterface;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class DeleteRoleAction
{
    public function __construct(
        private DeleteRoleInterface $deleteRole,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(DeleteRoleRequest $request): Response
    {
        try {
            try {
                $input = new DeleteRoleInput(
                    new RoleIdentifier($request->roleId()),
                );
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->deleteRole->process($input);
                DB::commit();
            } catch (RoleNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('role_not_found', $language), previous: $e);
            } catch (CannotDeleteSystemRoleException $e) {
                DB::rollBack();

                throw new ConflictHttpException(detail: error_message('cannot_delete_system_role', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (NotFoundHttpException|ConflictHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->noContent(Response::HTTP_NO_CONTENT);
    }
}
