<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\PrincipalGroup\Command\AddPrincipalToPrincipalGroup;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Account\Principal\Application\UseCase\Command\AddPrincipalToPrincipalGroup\AddPrincipalToPrincipalGroupInput;
use Source\Account\Principal\Application\UseCase\Command\AddPrincipalToPrincipalGroup\AddPrincipalToPrincipalGroupInterface;
use Source\Account\Principal\Application\UseCase\Command\AddPrincipalToPrincipalGroup\AddPrincipalToPrincipalGroupOutput;
use Source\Account\Principal\Domain\Exception\PrincipalAlreadyMemberException;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class AddPrincipalToPrincipalGroupAction
{
    public function __construct(
        private AddPrincipalToPrincipalGroupInterface $addPrincipalToPrincipalGroup,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param AddPrincipalToPrincipalGroupRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(AddPrincipalToPrincipalGroupRequest $request): JsonResponse
    {
        try {
            try {
                $input = new AddPrincipalToPrincipalGroupInput(
                    principalGroupIdentifier: new PrincipalGroupIdentifier($request->principalGroupId()),
                    principalIdentifier: new IdentityIdentifier($request->principalIdentifier()),
                );
                $output = new AddPrincipalToPrincipalGroupOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->addPrincipalToPrincipalGroup->process($input, $output);
                DB::commit();
            } catch (PrincipalGroupNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('principal_group_not_found', $language), previous: $e);
            } catch (PrincipalAlreadyMemberException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('identity_already_member', $language), previous: $e);
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
