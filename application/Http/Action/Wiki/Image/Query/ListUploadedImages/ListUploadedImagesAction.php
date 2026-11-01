<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Image\Query\ListUploadedImages;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Image\Application\UseCase\Query\ListUploadedImages\ListUploadedImagesInput;
use Source\Wiki\Image\Application\UseCase\Query\ListUploadedImages\ListUploadedImagesInterface;
use Source\Wiki\Image\Application\UseCase\Query\ListUploadedImages\ListUploadedImagesOutput;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListUploadedImagesAction
{
    public function __construct(
        private ListUploadedImagesInterface $listUploadedImages,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(ListUploadedImagesRequest $request): JsonResponse
    {
        try {
            $input = new ListUploadedImagesInput(
                perPage: $request->perPage(),
                translationSetIdentifier: new TranslationSetIdentifier($request->translationSetIdentifier()),
            );
            $output = new ListUploadedImagesOutput();
            $this->listUploadedImages->process($input, $output);
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
