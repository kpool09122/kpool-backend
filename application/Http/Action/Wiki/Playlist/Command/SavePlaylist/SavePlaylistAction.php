<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Playlist\Command\SavePlaylist;

use Application\Http\Action\Support\RequestValue;
use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Playlist\Application\UseCase\Command\SavePlaylist\SavePlaylistInput;
use Source\Wiki\Playlist\Application\UseCase\Command\SavePlaylist\SavePlaylistInterface;
use Source\Wiki\Playlist\Application\UseCase\Command\SavePlaylist\SavePlaylistItemInput;
use Source\Wiki\Playlist\Domain\Exception\PlaylistItemLimitExceededException;
use Source\Wiki\Playlist\Domain\Exception\PlaylistNotFoundException;
use Source\Wiki\Playlist\Domain\ValueObject\ExternalVideoId;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistName;
use Source\Wiki\Playlist\Domain\ValueObject\VideoProvider;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class SavePlaylistAction
{
    public function __construct(
        private SavePlaylistInterface $savePlaylist,
        private WikiContext $wikiContext,
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(SavePlaylistRequest $request): Response
    {
        try {
            try {
                $items = [];
                foreach ($request->items() as $item) {
                    $items[] = new SavePlaylistItemInput(
                        provider: isset($item['provider']) ? VideoProvider::from(RequestValue::string($item['provider'])) : null,
                        externalVideoId: isset($item['externalVideoId']) ? new ExternalVideoId(RequestValue::string($item['externalVideoId'])) : null,
                        videoLinkIdentifier: isset($item['videoLinkIdentifier']) ? new VideoLinkIdentifier(RequestValue::string($item['videoLinkIdentifier'])) : null,
                    );
                }
                $input = new SavePlaylistInput($this->wikiContext->principalIdentifier, new PlaylistIdentifier($request->playlistId()), new PlaylistName($request->name()), $items);
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: error_message('invalid_playlist_edit', $request->language()), previous: $e);
            }
            DB::beginTransaction();

            try {
                $this->savePlaylist->process($input);
                DB::commit();
            } catch (PlaylistNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('playlist_not_found', $request->language()), previous: $e);
            } catch (VideoLinkNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('video_link_not_found', $request->language()), previous: $e);
            } catch (DisallowedException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed', $request->language()), previous: $e);
            } catch (PlaylistItemLimitExceededException|InvalidArgumentException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_playlist_edit', $request->language()), previous: $e);
            } catch (PrincipalNotFoundException $e) {
                DB::rollBack();

                throw new InternalServerErrorHttpException(previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (NotFoundHttpException|ForbiddenHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->noContent(Response::HTTP_NO_CONTENT);
    }
}
