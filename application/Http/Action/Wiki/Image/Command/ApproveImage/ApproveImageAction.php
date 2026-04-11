<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Image\Command\ApproveImage;

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
use Source\Wiki\Image\Application\UseCase\Command\ApproveImage\ApproveImageInput;
use Source\Wiki\Image\Application\UseCase\Command\ApproveImage\ApproveImageInterface;
use Source\Wiki\Image\Application\UseCase\Command\ApproveImage\ApproveImageOutput;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ApproveImageAction
{
    public function __construct(
        private ApproveImageInterface $approveImage,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param ApproveImageRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(ApproveImageRequest $request): JsonResponse
    {
        try {
            try {
                $input = new ApproveImageInput(
                    new ImageIdentifier($request->imageId()),
                    new PrincipalIdentifier($request->principalId()),
                );
                $output = new ApproveImageOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->approveImage->process($input, $output);
                DB::commit();
            } catch (ImageNotFoundException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new NotFoundHttpException(detail: error_message('image_not_found', $language), previous: $e);
            } catch (DisallowedException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new ForbiddenHttpException(detail: error_message('disallowed', $language), previous: $e);
            } catch (InvalidStatusException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new ConflictHttpException(detail: error_message('allow_only_under_review_image_status', $language), previous: $e);
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

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
