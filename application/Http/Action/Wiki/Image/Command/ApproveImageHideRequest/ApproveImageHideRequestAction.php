<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Image\Command\ApproveImageHideRequest;

use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Application\UseCase\Command\ApproveImageHideRequest\ApproveImageHideRequestInput;
use Source\Wiki\Image\Application\UseCase\Command\ApproveImageHideRequest\ApproveImageHideRequestInterface;
use Source\Wiki\Image\Application\UseCase\Command\ApproveImageHideRequest\ApproveImageHideRequestOutput;
use Source\Wiki\Image\Domain\Exception\ImageHideRequestNotPendingException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ApproveImageHideRequestAction
{
    public function __construct(
        private ApproveImageHideRequestInterface $approveImageHideRequest,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param ApproveImageHideRequestRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(ApproveImageHideRequestRequest $request): JsonResponse
    {
        try {
            try {
                $input = new ApproveImageHideRequestInput(
                    new ImageIdentifier($request->imageId()),
                    new PrincipalIdentifier($request->principalId()),
                    $request->reviewerComment(),
                );
                $output = new ApproveImageHideRequestOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->approveImageHideRequest->process($input, $output);
                DB::commit();
            } catch (ImageNotFoundException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new NotFoundHttpException(detail: error_message('image_not_found', $language), previous: $e);
            } catch (ImageHideRequestNotPendingException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new ConflictHttpException(detail: error_message('image_hide_request_not_pending_for_approval', $language), previous: $e);
            } catch (DisallowedException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new ForbiddenHttpException(detail: error_message('disallowed', $language), previous: $e);
            } catch (PrincipalNotFoundException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw $e;
            }
        } catch (NotFoundHttpException|ForbiddenHttpException|ConflictHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
