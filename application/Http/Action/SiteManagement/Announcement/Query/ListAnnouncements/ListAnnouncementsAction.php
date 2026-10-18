<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Announcement\Query\ListAnnouncements;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListAnnouncements\ListAnnouncementsInput;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListAnnouncements\ListAnnouncementsInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Query\ListAnnouncements\ListAnnouncementsOutput;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListAnnouncementsAction
{
    public function __construct(private ListAnnouncementsInterface $listAnnouncements, private LoggerInterface $logger)
    {
    }

    /** @throws InternalServerErrorHttpException */
    public function __invoke(ListAnnouncementsRequest $request): JsonResponse
    {
        try {
            $output = new ListAnnouncementsOutput();
            $this->listAnnouncements->process(new ListAnnouncementsInput(Language::from($request->contentLanguage()), $request->perPage(), $request->order(), $request->sort(), $request->searchWords()), $output);
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: error_message('internal_server_error', $request->language()), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
