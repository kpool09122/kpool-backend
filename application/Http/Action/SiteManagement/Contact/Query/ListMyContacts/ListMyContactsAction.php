<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Contact\Query\ListMyContacts;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts\ListMyContactsInput;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts\ListMyContactsInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListMyContacts\ListMyContactsOutput;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class ListMyContactsAction
{
    public function __construct(
        private ListMyContactsInterface $listMyContacts,
        private ActorContext $actorContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(): JsonResponse
    {
        try {
            $output = new ListMyContactsOutput();
            $this->listMyContacts->process(
                new ListMyContactsInput($this->actorContext->identityIdentifier),
                $output,
            );
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
