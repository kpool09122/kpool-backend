<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\DelegationPermission\Command\RevokeDelegationPermission;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\DelegationPermission\Application\Exception\DelegationPermissionNotFoundException;
use Source\Account\DelegationPermission\Application\UseCase\Command\RevokeDelegationPermission\RevokeDelegationPermissionInput;
use Source\Account\DelegationPermission\Application\UseCase\Command\RevokeDelegationPermission\RevokeDelegationPermissionInterface;
use Source\Account\DelegationPermission\Domain\ValueObject\DelegationPermissionIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RevokeDelegationPermissionAction
{
    public function __construct(
        private RevokeDelegationPermissionInterface $revokeDelegationPermission,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param RevokeDelegationPermissionRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(RevokeDelegationPermissionRequest $request): JsonResponse
    {
        try {
            try {
                $input = new RevokeDelegationPermissionInput(
                    delegationPermissionIdentifier: new DelegationPermissionIdentifier($request->delegationPermissionId()),
                );
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->revokeDelegationPermission->process($input);
                DB::commit();
            } catch (DelegationPermissionNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('delegation_permission_not_found', $language), previous: $e);
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

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
