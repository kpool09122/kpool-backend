<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Principal\Command\DeletePolicy;

use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Principal\Application\Exception\CannotDeleteSystemPolicyException;
use Source\Wiki\Principal\Application\Exception\PolicyNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\DeletePolicy\DeletePolicyInput;
use Source\Wiki\Principal\Application\UseCase\Command\DeletePolicy\DeletePolicyInterface;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class DeletePolicyAction
{
    public function __construct(
        private DeletePolicyInterface $deletePolicy,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(DeletePolicyRequest $request): Response
    {
        try {
            try {
                $input = new DeletePolicyInput(
                    new PolicyIdentifier($request->policyId()),
                );
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->deletePolicy->process($input);
                DB::commit();
            } catch (PolicyNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('policy_not_found', $language), previous: $e);
            } catch (CannotDeleteSystemPolicyException $e) {
                DB::rollBack();

                throw new ConflictHttpException(detail: error_message('cannot_delete_system_policy', $language), previous: $e);
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
