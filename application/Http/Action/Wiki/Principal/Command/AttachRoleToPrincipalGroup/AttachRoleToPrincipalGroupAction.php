<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\AttachRoleToPrincipalGroup;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Application\Exception\RoleNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\AttachRoleToPrincipalGroup\AttachRoleToPrincipalGroupInput;
use Source\Wiki\Principal\Application\UseCase\Command\AttachRoleToPrincipalGroup\AttachRoleToPrincipalGroupInterface;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class AttachRoleToPrincipalGroupAction
{
    public function __construct(
        private AttachRoleToPrincipalGroupInterface $attachRoleToPrincipalGroup,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(AttachRoleToPrincipalGroupRequest $request): Response
    {
        try {
            try {
                $input = new AttachRoleToPrincipalGroupInput(
                    new PrincipalGroupIdentifier($request->principalGroupId()),
                    new RoleIdentifier($request->roleIdentifier()),
                );
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->attachRoleToPrincipalGroup->process($input);
                DB::commit();
            } catch (PrincipalGroupNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('principal_group_not_found', $language), previous: $e);
            } catch (RoleNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('role_not_found', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->noContent(Response::HTTP_NO_CONTENT);
    }
}
