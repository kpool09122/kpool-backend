<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\IdentityGroup\Command\RemoveIdentityFromIdentityGroup;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\IdentityGroup\Application\Exception\CannotRemoveLastOwnerException;
use Source\Account\IdentityGroup\Application\Exception\IdentityGroupNotFoundException;
use Source\Account\IdentityGroup\Application\UseCase\Command\RemoveIdentityFromIdentityGroup\RemoveIdentityFromIdentityGroupInput;
use Source\Account\IdentityGroup\Application\UseCase\Command\RemoveIdentityFromIdentityGroup\RemoveIdentityFromIdentityGroupInterface;
use Source\Account\IdentityGroup\Application\UseCase\Command\RemoveIdentityFromIdentityGroup\RemoveIdentityFromIdentityGroupOutput;
use Source\Account\IdentityGroup\Domain\Exception\IdentityNotMemberException;
use Source\Account\Shared\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RemoveIdentityFromIdentityGroupAction
{
    public function __construct(
        private RemoveIdentityFromIdentityGroupInterface $removeIdentityFromIdentityGroup,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param RemoveIdentityFromIdentityGroupRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(RemoveIdentityFromIdentityGroupRequest $request): JsonResponse
    {
        try {
            try {
                $input = new RemoveIdentityFromIdentityGroupInput(
                    identityGroupIdentifier: new IdentityGroupIdentifier($request->identityGroupId()),
                    identityIdentifier: new IdentityIdentifier($request->identityIdentifier()),
                );
                $output = new RemoveIdentityFromIdentityGroupOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->removeIdentityFromIdentityGroup->process($input, $output);
                DB::commit();
            } catch (IdentityGroupNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('identity_group_not_found', $language), previous: $e);
            } catch (IdentityNotMemberException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('identity_not_member', $language), previous: $e);
            } catch (CannotRemoveLastOwnerException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('cannot_remove_last_owner', $language), previous: $e);
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

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
