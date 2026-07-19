<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Image\Command\RequestImageDeletion;

use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Application\UseCase\Command\RequestImageDeletion\RequestImageDeletionInput;
use Source\Wiki\Image\Application\UseCase\Command\RequestImageDeletion\RequestImageDeletionInterface;
use Source\Wiki\Image\Application\UseCase\Command\RequestImageDeletion\RequestImageDeletionOutput;
use Source\Wiki\Image\Domain\Exception\ImageDeletionRequestAlreadyPendingException;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RequestImageDeletionAction
{
    public function __construct(
        private RequestImageDeletionInterface $requestImageDeletion,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param RequestImageDeletionRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(RequestImageDeletionRequest $request): JsonResponse
    {
        try {
            try {
                $input = new RequestImageDeletionInput(
                    new ImageIdentifier($request->imageId()),
                    $request->requesterName(),
                    $request->requesterEmail(),
                    $request->reason(),
                );
                $output = new RequestImageDeletionOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->requestImageDeletion->process($input, $output);
                DB::commit();
            } catch (ImageNotFoundException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new NotFoundHttpException(detail: error_message('image_not_found', $language), previous: $e);
            } catch (ImageDeletionRequestAlreadyPendingException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new ConflictHttpException(detail: error_message('image_deletion_request_already_pending', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw $e;
            }
        } catch (NotFoundHttpException|ConflictHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
