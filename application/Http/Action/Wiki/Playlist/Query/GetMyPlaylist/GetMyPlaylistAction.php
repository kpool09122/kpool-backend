<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Playlist\Query\GetMyPlaylist;

use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Playlist\Application\UseCase\Query\GetMyPlaylist\GetMyPlaylistInput;
use Source\Wiki\Playlist\Application\UseCase\Query\GetMyPlaylist\GetMyPlaylistInterface;
use Source\Wiki\Playlist\Application\UseCase\Query\GetMyPlaylist\GetMyPlaylistOutput;
use Source\Wiki\Playlist\Domain\Exception\PlaylistNotFoundException;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Throwable;

readonly class GetMyPlaylistAction
{
    public function __construct(
        private GetMyPlaylistInterface $getMyPlaylist,
        private WikiContext $wikiContext,
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(GetMyPlaylistRequest $request): JsonResponse
    {
        try {
            try {
                $input = new GetMyPlaylistInput($this->wikiContext->principalIdentifier, new PlaylistIdentifier($request->playlistId()));
                $output = new GetMyPlaylistOutput();

                $this->getMyPlaylist->process($input, $output);
            } catch (PlaylistNotFoundException $e) {
                throw new NotFoundHttpException(detail: error_message('playlist_not_found', $request->language()), previous: $e);
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: error_message('invalid_playlist_identifier', $request->language()), previous: $e);
            } catch (DisallowedException $e) {
                throw new ForbiddenHttpException(detail: error_message('disallowed', $request->language()), previous: $e);
            } catch (PrincipalNotFoundException $e) {
                throw new InternalServerErrorHttpException(previous: $e);
            }
        } catch (ForbiddenHttpException | NotFoundHttpException | UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json($output->toArray());
    }
}
