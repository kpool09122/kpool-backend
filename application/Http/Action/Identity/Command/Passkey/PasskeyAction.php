<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\Passkey;

use Application\Http\Context\ActorContext;
use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnauthorizedHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\UseCase\Command\Passkey\PasskeyUseCaseInterface;
use Source\Identity\Domain\Exception\AlreadyUserExistsException;
use Source\Identity\Domain\Exception\AuthCodeSessionNotFoundException;
use Source\Identity\Domain\Exception\ChallengeSessionNotFoundException;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Exception\InvalidPasskeyException;
use Source\Identity\Domain\Exception\LastAuthenticationMethodException;
use Source\Identity\Domain\Exception\PasskeyNotFoundException;
use Throwable;

readonly class PasskeyAction
{
    public function __construct(
        private PasskeyUseCaseInterface $useCase,
        private ActorContext $actorContext,
        private LoggerInterface $logger,
    ) {
    }

    public function beginSignup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identityName' => ['required', 'string', 'max:32'],
            'email' => ['required', 'email'],
            'language' => ['required', 'string'],
            'base64EncodedImage' => ['nullable', 'string'],
            'oneTimeToken' => ['nullable', 'string'],
        ]);

        return $this->respond(fn (): array => $this->useCase->beginSignup(
            $data['identityName'],
            $data['email'],
            $data['language'],
            $data['base64EncodedImage'] ?? null,
            $data['oneTimeToken'] ?? null,
        ));
    }

    public function finishSignup(Request $request): JsonResponse
    {
        $data = $this->validateCredentialRequest($request);

        return $this->respond(
            fn (): array => DB::transaction(fn (): array => $this->useCase->finishSignup(
                $data['challengeIdentifier'],
                $data['credential'],
                $data['displayName'],
            )),
            201,
        );
    }

    public function beginLogin(): JsonResponse
    {
        return $this->respond(fn (): array => $this->useCase->beginLogin());
    }

    public function finishLogin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'challengeIdentifier' => ['required', 'uuid'],
            'credential' => ['required', 'array'],
        ]);

        return $this->respond(fn (): array => DB::transaction(fn (): array => $this->useCase->finishLogin(
            $data['challengeIdentifier'],
            $data['credential'],
        )));
    }

    public function list(): JsonResponse
    {
        return $this->respond(fn (): array => ['passkeys' => $this->useCase->list($this->actorContext->identityIdentifier)]);
    }

    public function beginAdd(): JsonResponse
    {
        return $this->respond(fn (): array => $this->useCase->beginAdd($this->actorContext->identityIdentifier));
    }

    public function finishAdd(Request $request): JsonResponse
    {
        $data = $this->validateCredentialRequest($request);

        return $this->respond(
            fn (): array => DB::transaction(fn (): array => $this->useCase->finishAdd(
                $this->actorContext->identityIdentifier,
                $data['challengeIdentifier'],
                $data['credential'],
                $data['displayName'],
            )),
            201,
        );
    }

    public function rename(Request $request, string $passkeyIdentifier): JsonResponse
    {
        $data = $request->validate(['displayName' => ['required', 'string', 'max:100']]);

        return $this->respond(function () use ($passkeyIdentifier, $data): array {
            $this->useCase->rename($this->actorContext->identityIdentifier, $passkeyIdentifier, $data['displayName']);

            return [];
        });
    }

    public function delete(string $passkeyIdentifier): JsonResponse
    {
        return $this->respond(function () use ($passkeyIdentifier): array {
            DB::transaction(fn () => $this->useCase->delete($this->actorContext->identityIdentifier, $passkeyIdentifier));

            return [];
        });
    }

    /** @return array{challengeIdentifier: string, credential: array<string, mixed>, displayName: string} */
    private function validateCredentialRequest(Request $request): array
    {
        /** @var array{challengeIdentifier: string, credential: array<string, mixed>, displayName: string} */
        return $request->validate([
            'challengeIdentifier' => ['required', 'uuid'],
            'credential' => ['required', 'array'],
            'displayName' => ['required', 'string', 'max:100'],
        ]);
    }

    /** @param callable(): array<string, mixed> $operation */
    private function respond(callable $operation, int $status = 200): JsonResponse
    {
        try {
            return response()->json($operation(), $status);
        } catch (AlreadyUserExistsException $exception) {
            return $this->problem(new ConflictHttpException(detail: 'このメールアドレスは既に登録されています', previous: $exception));
        } catch (AuthCodeSessionNotFoundException|ChallengeSessionNotFoundException|IdentityNotFoundException|PasskeyNotFoundException $exception) {
            return $this->problem(new NotFoundHttpException(detail: $exception->getMessage() ?: '対象が見つかりません', previous: $exception));
        } catch (LastAuthenticationMethodException $exception) {
            return $this->problem(new ConflictHttpException(detail: $exception->getMessage(), previous: $exception));
        } catch (InvalidPasskeyException $exception) {
            return $this->problem(new UnprocessableEntityHttpException(detail: $exception->getMessage(), previous: $exception));
        } catch (ForbiddenHttpException|UnauthorizedHttpException $exception) {
            return $this->problem($exception);
        } catch (Throwable $exception) {
            $this->logger->error('Passkey operation failed.', ['exception' => $exception]);

            throw new InternalServerErrorHttpException(detail: 'パスキー処理に失敗しました', previous: $exception);
        }
    }

    private function problem(ConflictHttpException|NotFoundHttpException|UnprocessableEntityHttpException|ForbiddenHttpException|UnauthorizedHttpException $exception): JsonResponse
    {
        return response()->json($exception->toProblemDetails(), $exception->getHttpStatus());
    }
}
