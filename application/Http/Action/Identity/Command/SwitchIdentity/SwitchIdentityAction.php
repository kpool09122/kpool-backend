<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\SwitchIdentity;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\SwitchIdentity\SwitchIdentityInput;
use Source\Identity\Application\UseCase\Command\SwitchIdentity\SwitchIdentityInterface;
use Source\Identity\Application\UseCase\Command\SwitchIdentity\SwitchIdentityOutput;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Exception\InvalidDelegationException;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class SwitchIdentityAction
{
    public function __construct(
        private SwitchIdentityInterface $switchIdentity,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param SwitchIdentityRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(SwitchIdentityRequest $request): JsonResponse
    {
        try {
            try {
                $input = new SwitchIdentityInput(
                    currentIdentityIdentifier: new IdentityIdentifier($request->currentIdentityIdentifier()),
                    targetDelegationIdentifier: $request->targetDelegationIdentifier() !== null
                        ? new DelegationIdentifier($request->targetDelegationIdentifier())
                        : null,
                );
                $output = new SwitchIdentityOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->switchIdentity->process($input, $output);
                DB::commit();
            } catch (IdentityNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('identity_not_found', $language), previous: $e);
            } catch (InvalidDelegationException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_delegation', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (NotFoundHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_OK);
    }
}
