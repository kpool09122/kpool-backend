<?php

declare(strict_types=1);

namespace Application\Http\Action\Wiki\Playlist\Query\ListMyPlaylists;

use Application\Http\Context\WikiContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Wiki\Playlist\Application\UseCase\Query\ListMyPlaylists\ListMyPlaylistsInput;
use Source\Wiki\Playlist\Application\UseCase\Query\ListMyPlaylists\ListMyPlaylistsInterface;
use Source\Wiki\Playlist\Application\UseCase\Query\ListMyPlaylists\ListMyPlaylistsOutput;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Throwable;

readonly class ListMyPlaylistsAction
{
    public function __construct(
        private ListMyPlaylistsInterface $listMyPlaylists,
        private WikiContext $wikiContext,
        private LoggerInterface $logger,
    ) {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(ListMyPlaylistsRequest $request): JsonResponse
    {
        try {
            $input = new ListMyPlaylistsInput($this->wikiContext->principalIdentifier);
            $output = new ListMyPlaylistsOutput();

            try {
                $this->listMyPlaylists->process($input, $output);
            } catch (DisallowedException $e) {
                throw new ForbiddenHttpException(detail: error_message('disallowed', $request->language()), previous: $e);
            } catch (PrincipalNotFoundException $e) {
                throw new InternalServerErrorHttpException(previous: $e);
            }
        } catch (ForbiddenHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(previous: $e);
        }

        return response()->json($output->toArray());
    }
}
