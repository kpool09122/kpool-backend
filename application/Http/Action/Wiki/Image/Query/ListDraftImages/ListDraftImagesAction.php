<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Image\Query\ListDraftImages;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Image\Application\UseCase\Query\ListDraftImages\ListDraftImagesInput;
use Source\Wiki\Image\Application\UseCase\Query\ListDraftImages\ListDraftImagesInterface;
use Source\Wiki\Image\Application\UseCase\Query\ListDraftImages\ListDraftImagesOutput;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListDraftImagesAction
{
    public function __construct(
        private ListDraftImagesInterface $listDraftImages,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(ListDraftImagesRequest $request): JsonResponse
    {
        try {
            $input = new ListDraftImagesInput(
                translationSetIdentifier: $request->translationSetIdentifier() !== null ? new TranslationSetIdentifier($request->translationSetIdentifier()) : null,
                status: ApprovalStatus::from($request->status()),
                perPage: $request->perPage(),
            );
            $output = new ListDraftImagesOutput();
            $this->listDraftImages->process($input, $output);
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
