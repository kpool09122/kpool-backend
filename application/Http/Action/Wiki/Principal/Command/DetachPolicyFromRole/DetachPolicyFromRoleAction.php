<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\DetachPolicyFromRole;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Principal\Application\Exception\RoleNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\DetachPolicyFromRole\DetachPolicyFromRoleInput;
use Source\Wiki\Principal\Application\UseCase\Command\DetachPolicyFromRole\DetachPolicyFromRoleInterface;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class DetachPolicyFromRoleAction
{
    public function __construct(
        private DetachPolicyFromRoleInterface $detachPolicyFromRole,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(DetachPolicyFromRoleRequest $request): Response
    {
        try {
            try {
                $input = new DetachPolicyFromRoleInput(
                    new RoleIdentifier($request->roleId()),
                    new PolicyIdentifier($request->policyIdentifier()),
                );
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->detachPolicyFromRole->process($input);
                DB::commit();
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
