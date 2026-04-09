<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\CreateIdentity;

use Application\Http\Exceptions\ConflictHttpException;
use Application\Http\Exceptions\ForbiddenHttpException;
use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\NotFoundHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Invitation\Domain\ValueObject\InvitationToken;
use Source\Identity\Application\UseCase\Command\CreateIdentity\CreateIdentityInput;
use Source\Identity\Application\UseCase\Command\CreateIdentity\CreateIdentityInterface;
use Source\Identity\Application\UseCase\Command\CreateIdentity\CreateIdentityOutput;
use Source\Identity\Domain\Exception\AlreadyUserExistsException;
use Source\Identity\Domain\Exception\AuthCodeSessionNotFoundException;
use Source\Identity\Domain\Exception\PasswordMismatchException;
use Source\Identity\Domain\Exception\UnauthorizedEmailException;
use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Identity\Domain\ValueObject\UserName;
use Source\Shared\Application\Exception\InvalidBase64ImageException;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class CreateIdentityAction
{
    public function __construct(
        private CreateIdentityInterface $createIdentity,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param CreateIdentityRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(CreateIdentityRequest $request): JsonResponse
    {
        try {
            try {
                $input = new CreateIdentityInput(
                    username: new UserName($request->username()),
                    email: new Email($request->email()),
                    language: Language::from($request->language()),
                    password: new PlainPassword($request->password()),
                    confirmedPassword: new PlainPassword($request->confirmedPassword()),
                    base64EncodedImage: $request->base64EncodedImage(),
                    invitationToken: $request->invitationToken() !== null
                        ? new InvitationToken($request->invitationToken())
                        : null,
                );
                $output = new CreateIdentityOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->requestLanguage();

            try {
                $this->createIdentity->process($input, $output);
                DB::commit();
            } catch (PasswordMismatchException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('password_mismatch', $language), previous: $e);
            } catch (AuthCodeSessionNotFoundException $e) {
                DB::rollBack();

                throw new NotFoundHttpException(detail: error_message('auth_code_session_not_found', $language), previous: $e);
            } catch (AlreadyUserExistsException $e) {
                DB::rollBack();

                throw new ConflictHttpException(detail: error_message('already_user_exists', $language), previous: $e);
            } catch (UnauthorizedEmailException $e) {
                DB::rollBack();

                throw new ForbiddenHttpException(detail: error_message('unauthorized_email', $language), previous: $e);
            } catch (InvalidBase64ImageException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_base64_image', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (NotFoundHttpException|ConflictHttpException|ForbiddenHttpException|UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json($output->toArray(), Response::HTTP_CREATED);
    }
}
