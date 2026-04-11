<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\DeletePrincipalGroup;

use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Principal\Application\Exception\CannotDeleteDefaultPrincipalGroupException;
use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\DeletePrincipalGroup\DeletePrincipalGroupInput;
use Source\Wiki\Principal\Application\UseCase\Command\DeletePrincipalGroup\DeletePrincipalGroupInterface;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class DeletePrincipalGroupAction
{
    public function __construct(
        private DeletePrincipalGroupInterface $deletePrincipalGroup,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(DeletePrincipalGroupRequest $request): Response
    {
        try {
            try {
                $input = new DeletePrincipalGroupInput(
                    new PrincipalGroupIdentifier($request->principalGroupId()),
                );
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->deletePrincipalGroup->process($input);
                DB::commit();
            } catch (PrincipalGroupNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('principal_group_not_found', $language), previous: $e);
            } catch (CannotDeleteDefaultPrincipalGroupException $e) {
                DB::rollBack();

                throw new ConflictHttpException(detail: error_message('cannot_delete_default_principal_group', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (NotFoundHttpException|ConflictHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->noContent(Response::HTTP_NO_CONTENT);
    }
}
