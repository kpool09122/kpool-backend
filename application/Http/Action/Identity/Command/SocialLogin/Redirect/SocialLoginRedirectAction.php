<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Command\SocialLogin\Redirect;

use Application\Http\Exceptions\InternalServerErrorHttpException;
use Application\Http\Exceptions\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Invitation\Domain\ValueObject\InvitationToken;
use Source\Identity\Application\UseCase\Command\SocialLogin\Redirect\SocialLoginRedirectInput;
use Source\Identity\Application\UseCase\Command\SocialLogin\Redirect\SocialLoginRedirectInterface;
use Source\Identity\Application\UseCase\Command\SocialLogin\Redirect\SocialLoginRedirectOutput;
use Source\Identity\Domain\Exception\InvalidOAuthStateException;
use Source\Identity\Domain\ValueObject\SignupSession;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class SocialLoginRedirectAction
{
    public function __construct(
        private SocialLoginRedirectInterface $socialLoginRedirect,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param SocialLoginRedirectRequest $request
     * @return JsonResponse
     * @throws InternalServerErrorHttpException
     */
    public function __invoke(SocialLoginRedirectRequest $request): JsonResponse
    {
        try {
            try {
                $provider = SocialProvider::fromString($request->provider());
                $signupSession = ($request->accountType() !== null || $request->invitationToken() !== null)
                    ? new SignupSession(
                        accountType: $request->accountType() !== null
                            ? AccountType::from($request->accountType())
                            : null,
                        invitationToken: $request->invitationToken() !== null
                            ? new InvitationToken($request->invitationToken())
                            : null,
                    )
                    : null;

                $input = new SocialLoginRedirectInput(
                    provider: $provider,
                    signupSession: $signupSession,
                );
                $output = new SocialLoginRedirectOutput();
            } catch (InvalidArgumentException $e) {
                throw new UnprocessableEntityHttpException(detail: $e->getMessage(), previous: $e);
            }

            DB::beginTransaction();

            $language = $request->language();

            try {
                $this->socialLoginRedirect->process($input, $output);
                DB::commit();
            } catch (InvalidOAuthStateException $e) {
                DB::rollBack();

                throw new UnprocessableEntityHttpException(detail: error_message('invalid_oauth_state', $language), previous: $e);
            } catch (Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (UnprocessableEntityHttpException $e) {
            $this->logger->error((string) $e);

            return response()->json($e->toProblemDetails(), $e->getHttpStatus());
        } catch (Throwable $e) {
            $this->logger->error((string) $e);

            throw new InternalServerErrorHttpException(detail: $e->getMessage(), previous: $e);
        }

        return response()->json(['redirectUrl' => $output->redirectUrl()], Response::HTTP_OK);
    }
}
