<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\VideoLink\Command\CreateVideoLink;

use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\BadGatewayHttpException;
use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Shared\Application\Service\VideoThumbnailServiceInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Service\VideoUrlBuilderInterface;
use Source\Wiki\Shared\Domain\ValueObject\DurationSeconds;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\ThumbnailUrl;
use Source\Wiki\Shared\Domain\ValueObject\VideoTitle;
use Source\Wiki\VideoLink\Application\Exception\VideoMetadataUnavailableException;
use Source\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink\CreateVideoLinkInput;
use Source\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink\CreateVideoLinkInterface;
use Source\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink\CreateVideoLinkOutput;
use Source\Wiki\VideoLink\Domain\Exception\VideoLinkAlreadyExistsException;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class CreateVideoLinkAction
{
    public function __construct(
        private CreateVideoLinkInterface $createVideoLink,
        private WikiContext $wikiContext,
        private LoggerInterface $logger,
        private VideoThumbnailServiceInterface $videoThumbnailService,
        private VideoUrlBuilderInterface $videoUrlBuilder,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(CreateVideoLinkRequest $request): Response
    {
        $output = new CreateVideoLinkOutput();
        $committed = false;

        try {
            try {
                $input = new CreateVideoLinkInput(
                    $this->wikiContext->principalIdentifier,
                    ResourceType::from($request->resourceType()),
                    new WikiIdentifier($request->wikiIdentifier()),
                    $this->videoUrlBuilder->build($request->url()),
                    VideoUsage::from($request->videoUsage()),
                    new VideoTitle($request->title()),
                    $request->durationSeconds() !== null ? new DurationSeconds($request->durationSeconds()) : null,
                    $request->thumbnailUrl() !== null ? new ThumbnailUrl($request->thumbnailUrl()) : null,
                    $request->publishedAt() !== null ? new DateTimeImmutable($request->publishedAt()) : null,
                );
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: error_message('invalid_video_link', $request->language()), previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->createVideoLink->process($input, $output);
                DB::commit();
                $committed = true;
            } catch (VideoMetadataUnavailableException $e) {
                DB::rollBack();

                throw new BadGatewayHttpException(detail: error_message('video_metadata_unavailable', $request->language()));
            } catch (InvalidArgumentException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_video_link', $request->language()), previous: $e);
            } catch (DisallowedException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed', $request->language()), previous: $e);
            } catch (WikiNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('wiki_not_found', $request->language()), previous: $e);
            } catch (VideoLinkAlreadyExistsException $e) {
                DB::rollBack();

                throw new ConflictHttpException(detail: error_message('video_link_already_exists', $request->language()), previous: $e);
            } catch (PrincipalNotFoundException $e) {
                DB::rollBack();

                throw new InternalServerErrorHttpException(previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (BadGatewayHttpException $e) {
            $this->logger->warning('Video metadata retrieval failed.');

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (ForbiddenHttpException|NotFoundHttpException|ConflictHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        } finally {
            if (! $committed && $output->thumbnailPath() !== null) {
                try {
                    $this->videoThumbnailService->track($output->thumbnailPath());
                    $this->videoThumbnailService->releaseUnused();
                } catch (Throwable) {
                    $this->logger->warning('Failed to compensate a newly stored video thumbnail.');
                }
            }
        }

        return response()->noContent(Response::HTTP_NO_CONTENT);
    }
}
