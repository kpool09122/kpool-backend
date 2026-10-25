<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Playlist\Command\CreatePlaylist;

use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Wiki\Playlist\Application\UseCase\Command\CreatePlaylist\CreatePlaylistInput;
use Source\Wiki\Playlist\Application\UseCase\Command\CreatePlaylist\CreatePlaylistInterface;
use Source\Wiki\Playlist\Application\UseCase\Command\CreatePlaylist\CreatePlaylistOutput;
use Source\Wiki\Playlist\Domain\Exception\PlaylistLimitExceededException;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistName;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class CreatePlaylistAction
{
    public function __construct(
        private CreatePlaylistInterface $createPlaylist,
        private WikiContext $wikiContext,
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(CreatePlaylistRequest $request): Response
    {
        try {
            try {
                $input = new CreatePlaylistInput($this->wikiContext->principalIdentifier, new PlaylistName($request->name()));
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: error_message('invalid_playlist_name', $request->language()), previous: $e);
            }

            $output = new CreatePlaylistOutput();
            DB::beginTransaction();

            try {
                $this->createPlaylist->process($input, $output);
                DB::commit();
            } catch (DisallowedException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('disallowed', $request->language()), previous: $e);
            } catch (PlaylistLimitExceededException $e) {
                DB::rollBack();

                throw new ConflictHttpException(detail: error_message('playlist_limit_exceeded', $request->language()), previous: $e);
            } catch (PrincipalNotFoundException $e) {
                DB::rollBack();

                throw new InternalServerErrorHttpException(previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (ConflictHttpException|ForbiddenHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
