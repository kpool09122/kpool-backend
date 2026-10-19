<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Contact\Query\GetContactDetailByIdentifier;

use Application\Http\Context\ActorContext;
use Application\Http\Context\SiteManagementContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\SiteManagement\Contact\Application\UseCase\Exception\ContactNotFoundException;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetailByIdentifier\GetContactDetailByIdentifierInput;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetailByIdentifier\GetContactDetailByIdentifierInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetailByIdentifier\GetContactDetailByIdentifierOutput;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class GetContactDetailByIdentifierAction
{
    public function __construct(
        private GetContactDetailByIdentifierInterface $getContactDetailByIdentifier,
        private SiteManagementContext $siteManagementContext,
        private ActorContext $actorContext,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws ForbiddenHttpException
     * @throws InternalServerErrorHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(GetContactDetailByIdentifierRequest $request): JsonResponse
    {
        try {
            $output = new GetContactDetailByIdentifierOutput();
            $this->getContactDetailByIdentifier->process(
                new GetContactDetailByIdentifierInput(
                    $this->siteManagementContext->principalIdentifier,
                    new ContactIdentifier($request->contactIdentifier()),
                ),
                $output,
            );
        } catch (UnauthorizedException $e) {
            throw new ForbiddenHttpException(detail: error_message('unauthorized', $this->actorContext->language->value), previous: $e);
        } catch (ContactNotFoundException $e) {
            throw new NotFoundHttpException(detail: error_message('contact_not_found', $this->actorContext->language->value), previous: $e);
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: error_message('internal_server_error', $this->actorContext->language->value), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
