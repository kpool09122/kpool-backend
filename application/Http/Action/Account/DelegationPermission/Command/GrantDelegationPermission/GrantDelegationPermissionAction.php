<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\DelegationPermission\Command\GrantDelegationPermission;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\DelegationPermission\Application\UseCase\Command\GrantDelegationPermission\GrantDelegationPermissionInput;
use Source\Account\DelegationPermission\Application\UseCase\Command\GrantDelegationPermission\GrantDelegationPermissionInterface;
use Source\Account\DelegationPermission\Application\UseCase\Command\GrantDelegationPermission\GrantDelegationPermissionOutput;
use Source\Account\IdentityGroup\Application\Exception\IdentityGroupNotFoundException;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Shared\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class GrantDelegationPermissionAction
{
    public function __construct(
        private GrantDelegationPermissionInterface $grantDelegationPermission,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param GrantDelegationPermissionRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(GrantDelegationPermissionRequest $request): JsonResponse
    {
        try {
            try {
                $input = new GrantDelegationPermissionInput(
                    identityGroupIdentifier: new IdentityGroupIdentifier($request->identityGroupIdentifier()),
                    targetAccountIdentifier: new AccountIdentifier($request->targetAccountIdentifier()),
                    affiliationIdentifier: new AffiliationIdentifier($request->affiliationIdentifier()),
                );
                $output = new GrantDelegationPermissionOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->grantDelegationPermission->process($input, $output);
                DB::commit();
            } catch (IdentityGroupNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('identity_group_not_found', $language), previous: $e);
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

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
