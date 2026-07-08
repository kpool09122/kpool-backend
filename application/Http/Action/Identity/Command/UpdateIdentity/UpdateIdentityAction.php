<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\UpdateIdentity;

use Application\Http\Action\Identity\Support\IdentityResponsePayload;
use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\UpdateIdentity\UpdateIdentityInput;
use Source\Identity\Application\UseCase\Command\UpdateIdentity\UpdateIdentityInterface;
use Source\Identity\Application\UseCase\Command\UpdateIdentity\UpdateIdentityOutput;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Exception\InvalidDelegationException;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Shared\Application\Exception\InvalidBase64ImageException;
use Source\Shared\Domain\ValueObject\Language;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class UpdateIdentityAction
{
    public function __construct(
        private UpdateIdentityInterface $updateIdentity,
        private ActorContext $actorContext,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(UpdateIdentityRequest $request): JsonResponse
    {
        try {
            try {
                $input = new UpdateIdentityInput(
                    identityIdentifier: $this->actorContext->identityIdentifier,
                    delegationIdentifier: $this->actorContext->delegationIdentifier,
                    originalIdentityIdentifier: $this->actorContext->originalIdentityIdentifier,
                    identityName: $request->identityName() !== null ? new IdentityName($request->identityName()) : null,
                    language: $request->language() !== null ? Language::from($request->language()) : null,
                    base64EncodedImage: $request->base64EncodedImage(),
                    profileImageProvided: $request->exists('base64EncodedImage'),
                );
                $output = new UpdateIdentityOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();
            $language = $request->requestLanguage();

            try {
                $this->updateIdentity->process($input, $output);
                DB::commit();
            } catch (IdentityNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('identity_not_found', $language), previous: $e);
            } catch (InvalidDelegationException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: $e->getMessage(), previous: $e);
            } catch (InvalidBase64ImageException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_base64_image', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (NotFoundHttpException|ForbiddenHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json(IdentityResponsePayload::normalizeProfileImage($output->toArray()), Response::HTTP_OK);
    }
}
