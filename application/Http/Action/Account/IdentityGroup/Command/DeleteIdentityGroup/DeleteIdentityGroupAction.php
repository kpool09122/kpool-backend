<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\IdentityGroup\Command\DeleteIdentityGroup;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\IdentityGroup\Application\Exception\CannotDeleteDefaultIdentityGroupException;
use Source\Account\IdentityGroup\Application\Exception\CannotDeleteLastOwnerGroupException;
use Source\Account\IdentityGroup\Application\Exception\IdentityGroupNotFoundException;
use Source\Account\IdentityGroup\Application\UseCase\Command\DeleteIdentityGroup\DeleteIdentityGroupInput;
use Source\Account\IdentityGroup\Application\UseCase\Command\DeleteIdentityGroup\DeleteIdentityGroupInterface;
use Source\Account\Shared\Domain\ValueObject\IdentityGroupIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class DeleteIdentityGroupAction
{
    public function __construct(
        private DeleteIdentityGroupInterface $deleteIdentityGroup,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param DeleteIdentityGroupRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(DeleteIdentityGroupRequest $request): JsonResponse
    {
        try {
            try {
                $input = new DeleteIdentityGroupInput(
                    identityGroupIdentifier: new IdentityGroupIdentifier($request->identityGroupId()),
                );
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->deleteIdentityGroup->process($input);
                DB::commit();
            } catch (IdentityGroupNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('identity_group_not_found', $language), previous: $e);
            } catch (CannotDeleteDefaultIdentityGroupException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('cannot_delete_default_identity_group', $language), previous: $e);
            } catch (CannotDeleteLastOwnerGroupException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('cannot_delete_last_owner_group', $language), previous: $e);
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

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
