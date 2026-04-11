<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\ImageHideRequest\Command\RequestImageHide;

use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\ImageHideRequest\Application\Exception\ImageHideRequestAlreadyPendingException;
use Source\Wiki\ImageHideRequest\Application\UseCase\Command\RequestImageHide\RequestImageHideInput;
use Source\Wiki\ImageHideRequest\Application\UseCase\Command\RequestImageHide\RequestImageHideInterface;
use Source\Wiki\ImageHideRequest\Application\UseCase\Command\RequestImageHide\RequestImageHideOutput;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RequestImageHideAction
{
    public function __construct(
        private RequestImageHideInterface $requestImageHide,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param RequestImageHideRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(RequestImageHideRequest $request): JsonResponse
    {
        try {
            try {
                $input = new RequestImageHideInput(
                    new ImageIdentifier($request->imageIdentifier()),
                    $request->requesterName(),
                    $request->requesterEmail(),
                    $request->reason(),
                );
                $output = new RequestImageHideOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->requestImageHide->process($input, $output);
                DB::commit();
            } catch (ImageNotFoundException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new NotFoundHttpException(detail: error_message('image_not_found', $language), previous: $e);
            } catch (ImageHideRequestAlreadyPendingException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new ConflictHttpException(detail: error_message('image_hide_request_already_pending', $language), previous: $e);
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
