<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Playlist\Command\DeletePlaylist;

use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Playlist\Application\UseCase\Command\DeletePlaylist\DeletePlaylistInput;
use Source\Wiki\Playlist\Application\UseCase\Command\DeletePlaylist\DeletePlaylistInterface;
use Source\Wiki\Playlist\Domain\Exception\PlaylistNotFoundException;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class DeletePlaylistAction
{
    public function __construct(
        private DeletePlaylistInterface $deletePlaylist,
        private WikiContext $wikiContext,
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(DeletePlaylistRequest $request): Response
    {
        try {
            try {
                $input = new DeletePlaylistInput($this->wikiContext->principalIdentifier, new PlaylistIdentifier($request->playlistId()));
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: error_message('invalid_playlist_identifier', $request->language()), previous: $e);
            }

            DB::beginTransaction();

            try {
                $this->deletePlaylist->process($input);
                DB::commit();
            } catch (DisallowedException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed', $request->language()), previous: $e);
            } catch (PlaylistNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('playlist_not_found', $request->language()), previous: $e);
            } catch (PrincipalNotFoundException $e) {
                DB::rollBack();

                throw new InternalServerErrorHttpException(previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (ForbiddenHttpException|NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->noContent(Response::HTTP_NO_CONTENT);
    }
}
