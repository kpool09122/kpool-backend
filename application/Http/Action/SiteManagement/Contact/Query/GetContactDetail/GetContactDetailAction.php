<?php

declare(strict_types=1);

namespace Application\Http\Action\SiteManagement\Contact\Query\GetContactDetail;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Application\UseCase\Exception\ContactNotFoundException;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailInput;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailInterface;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailOutput;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class GetContactDetailAction
{
    public function __construct(
        private GetContactDetailInterface $getContactDetail,
        private ActorContext $actorContext,
        // @phpstan-ignore property.onlyWritten
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws ForbiddenHttpException
     * @throws InternalServerErrorHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(GetContactDetailRequest $request): JsonResponse
    {
        try {
            $output = new GetContactDetailOutput();
            $this->getContactDetail->process(
                new GetContactDetailInput(
                    $this->actorContext->identityIdentifier,
                    new IdentityIdentifier($request->identityIdentifier()),
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

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
