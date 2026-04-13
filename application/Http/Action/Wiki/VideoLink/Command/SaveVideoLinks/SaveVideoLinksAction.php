<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\VideoLink\Command\SaveVideoLinks;

use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Application\UseCase\Command\SaveVideoLinks\SaveVideoLinksInput;
use Source\Wiki\VideoLink\Application\UseCase\Command\SaveVideoLinks\SaveVideoLinksInterface;
use Source\Wiki\VideoLink\Application\UseCase\Command\SaveVideoLinks\VideoLinkData;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use ValueError;

readonly class SaveVideoLinksAction
{
    public function __construct(
        private SaveVideoLinksInterface $saveVideoLinks,
        private WikiContext $wikiContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(SaveVideoLinksRequest $request): Response
    {
        try {
            try {
                $videoLinkDataArray = array_map(
                    static fn (array $item): VideoLinkData => new VideoLinkData(
                        new ExternalContentLink($item['url']),
                        VideoUsage::from($item['videoUsage']),
                        $item['title'] ?? '',
                        $item['displayOrder'],
                        $item['thumbnailUrl'] ?? null,
                        isset($item['publishedAt']) ? new DateTimeImmutable($item['publishedAt']) : null,
                    ),
                    $request->videoLinks(),
                );

                $input = new SaveVideoLinksInput(
                    $this->wikiContext->principalIdentifier,
                    ResourceType::from($request->resourceType()),
                    new WikiIdentifier($request->wikiIdentifier()),
                    $videoLinkDataArray,
                );
            } catch (InvalidArgumentException|ValueError $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->saveVideoLinks->process($input);
                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->noContent(Response::HTTP_NO_CONTENT);
    }
}
