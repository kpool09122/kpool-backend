<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\DetachRoleFromPrincipalGroup;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\DetachRoleFromPrincipalGroup\DetachRoleFromPrincipalGroupInput;
use Source\Wiki\Principal\Application\UseCase\Command\DetachRoleFromPrincipalGroup\DetachRoleFromPrincipalGroupInterface;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class DetachRoleFromPrincipalGroupAction
{
    public function __construct(
        private DetachRoleFromPrincipalGroupInterface $detachRoleFromPrincipalGroup,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(DetachRoleFromPrincipalGroupRequest $request): Response
    {
        try {
            try {
                $input = new DetachRoleFromPrincipalGroupInput(
                    new PrincipalGroupIdentifier($request->principalGroupId()),
                    new RoleIdentifier($request->roleIdentifier()),
                );
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->detachRoleFromPrincipalGroup->process($input);
                DB::commit();
            } catch (PrincipalGroupNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('principal_group_not_found', $language), previous: $e);
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
