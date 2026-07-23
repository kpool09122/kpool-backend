<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\PrincipalGroup\Command\RemovePrincipalFromPrincipalGroup;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Principal\Application\Exception\CannotRemoveLastOwnerException;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Account\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupInput;
use Source\Account\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupInterface;
use Source\Account\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupOutput;
use Source\Account\Principal\Domain\Exception\PrincipalNotMemberException;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RemovePrincipalFromPrincipalGroupAction
{
    public function __construct(
        private RemovePrincipalFromPrincipalGroupInterface $removePrincipalFromPrincipalGroup,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param RemovePrincipalFromPrincipalGroupRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(RemovePrincipalFromPrincipalGroupRequest $request): JsonResponse
    {
        try {
            try {
                $input = new RemovePrincipalFromPrincipalGroupInput(
                    principalGroupIdentifier: new PrincipalGroupIdentifier($request->principalGroupId()),
                    principalIdentifier: new IdentityIdentifier($request->principalIdentifier()),
                );
                $output = new RemovePrincipalFromPrincipalGroupOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->removePrincipalFromPrincipalGroup->process($input, $output);
                DB::commit();
            } catch (PrincipalGroupNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('principal_group_not_found', $language), previous: $e);
            } catch (PrincipalNotMemberException $e) {
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
