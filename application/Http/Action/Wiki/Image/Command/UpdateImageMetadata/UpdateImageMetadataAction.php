<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Image\Command\UpdateImageMetadata;

use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Image\Application\Exception\ImageNotFoundException;
use Source\Wiki\Image\Application\UseCase\Command\UpdateImageMetadata\UpdateImageMetadataInput;
use Source\Wiki\Image\Application\UseCase\Command\UpdateImageMetadata\UpdateImageMetadataInterface;
use Source\Wiki\Image\Application\UseCase\Command\UpdateImageMetadata\UpdateImageMetadataOutput;
use Source\Wiki\Image\Domain\ValueObject\ImageAuthor;
use Source\Wiki\Image\Domain\ValueObject\ImageLicense;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class UpdateImageMetadataAction
{
    public function __construct(
        private UpdateImageMetadataInterface $updateImageMetadata,
        private WikiContext $wikiContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param UpdateImageMetadataRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(UpdateImageMetadataRequest $request): JsonResponse
    {
        try {
            try {
                $input = new UpdateImageMetadataInput(
                    new ImageIdentifier($request->imageId()),
                    $this->wikiContext->principalIdentifier,
                    $request->string('sourceName')->toString(),
                    new ImageAuthor($request->string('author')->toString()),
                    ImageLicense::from($request->string('license')->toString()),
                    $request->string('altText')->toString(),
                    $request->integer('displayOrder'),
                );
                $output = new UpdateImageMetadataOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->updateImageMetadata->process($input, $output);
                DB::commit();
            } catch (ImageNotFoundException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new NotFoundHttpException(detail: error_message('image_not_found', $language), previous: $e);
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
        } catch (NotFoundHttpException|ForbiddenHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
