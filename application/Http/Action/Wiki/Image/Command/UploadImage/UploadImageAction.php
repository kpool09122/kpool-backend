<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Image\Command\UploadImage;

use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Shared\Application\Exception\InvalidBase64ImageException;
use Source\Wiki\Image\Application\UseCase\Command\UploadImage\UploadImageInput;
use Source\Wiki\Image\Application\UseCase\Command\UploadImage\UploadImageInterface;
use Source\Wiki\Image\Application\UseCase\Command\UploadImage\UploadImageOutput;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class UploadImageAction
{
    public function __construct(
        private UploadImageInterface $uploadImage,
        private WikiContext $wikiContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param UploadImageRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(UploadImageRequest $request): JsonResponse
    {
        try {
            try {
                $input = new UploadImageInput(
                    $this->wikiContext->principalIdentifier,
                    $request->publishedImageIdentifier() !== null ? new ImageIdentifier($request->publishedImageIdentifier()) : null,
                    ResourceType::from($request->resourceType()),
                    new WikiIdentifier($request->wikiIdentifier()),
                    $request->base64EncodedImage(),
                    ImageUsage::from($request->imageUsage()),
                    (int) $request->displayOrder(),
                    $request->sourceUrl(),
                    $request->sourceName(),
                    $request->altText(),
                    new DateTimeImmutable($request->agreedToTermsAt()),
                );
                $output = new UploadImageOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->uploadImage->process($input, $output);
                DB::commit();
            } catch (InvalidBase64ImageException $e) {
                DB::rollBack();
                $this->logger->error((string) $e);

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_base64_image', $language), previous: $e);
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
        } catch (ForbiddenHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
