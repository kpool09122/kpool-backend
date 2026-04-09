<?php

declare(strict_types=1);

namespace Application\Http\Action\Account\IdentityGroup\Command\AddIdentityToIdentityGroup;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\IdentityGroup\Application\Exception\IdentityGroupNotFoundException;
use Source\Account\IdentityGroup\Application\UseCase\Command\AddIdentityToIdentityGroup\AddIdentityToIdentityGroupInput;
use Source\Account\IdentityGroup\Application\UseCase\Command\AddIdentityToIdentityGroup\AddIdentityToIdentityGroupInterface;
use Source\Account\IdentityGroup\Application\UseCase\Command\AddIdentityToIdentityGroup\AddIdentityToIdentityGroupOutput;
use Source\Account\IdentityGroup\Domain\Exception\IdentityAlreadyMemberException;
use Source\Account\Shared\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class AddIdentityToIdentityGroupAction
{
    public function __construct(
        private AddIdentityToIdentityGroupInterface $addIdentityToIdentityGroup,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param AddIdentityToIdentityGroupRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(AddIdentityToIdentityGroupRequest $request): JsonResponse
    {
        try {
            try {
                $input = new AddIdentityToIdentityGroupInput(
                    identityGroupIdentifier: new IdentityGroupIdentifier($request->identityGroupId()),
                    identityIdentifier: new IdentityIdentifier($request->identityIdentifier()),
                );
                $output = new AddIdentityToIdentityGroupOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->addIdentityToIdentityGroup->process($input, $output);
                DB::commit();
            } catch (IdentityGroupNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('identity_group_not_found', $language), previous: $e);
            } catch (IdentityAlreadyMemberException $e) {
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
