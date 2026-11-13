<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Image\Query\ListManagedImages;

use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Image\Application\UseCase\Query\ListManagedImages\ListManagedImagesInput;
use Source\Wiki\Image\Application\UseCase\Query\ListManagedImages\ListManagedImagesInterface;
use Source\Wiki\Image\Application\UseCase\Query\ListManagedImages\ListManagedImagesOutput;
use Source\Wiki\Image\Domain\ValueObject\ImageLicense;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListManagedImagesAction
{
    public function __construct(
        private ListManagedImagesInterface $listManagedImages,
        private WikiContext $wikiContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(ListManagedImagesRequest $request): JsonResponse
    {
        try {
            try {
                $license = $request->license();
                $input = new ListManagedImagesInput(
                    principalIdentifier: $this->wikiContext->principalIdentifier,
                    perPage: $request->perPage(),
                    license: $license === null ? null : ImageLicense::from($license),
                    authorMissing: $request->boolean('authorMissing'),
                    licenseMissing: $request->boolean('licenseMissing'),
                    status: ApprovalStatus::from($request->string('status')->toString()),
                );
                $output = new ListManagedImagesOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            try {
                $this->listManagedImages->process($input, $output);
            } catch (DisallowedException $e) {
                $this->logger->error((string) $e);

                throw new ForbiddenHttpException(detail: error_message('disallowed', $request->language()), previous: $e);
            } catch (PrincipalNotFoundException $e) {
                $this->logger->error((string) $e);

                throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
            }
        } catch (ForbiddenHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
