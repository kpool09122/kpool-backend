using TypeSpec.Http;
using KPool.Common;

@service(#{ title: "k-pool Identity API" })
@server("/api/identity", "Laravel route prefix from bootstrap/app.php.")
namespace KPool.IdentityApi;

@doc("Identity summary returned after authentication-related operations.")
model IdentitySummary {
  @doc("Identity identifier.")
  identityIdentifier: Uuid;
  @doc("Username.")
  username: string;
  @doc("Email address.")
  email: string;
  @doc("Language code associated with the identity.")
  language: string;
  @doc("Profile image path.")
  profileImage?: string | null;
}

@doc("Identity summary returned after switching active identity.")
model SwitchedIdentitySummary extends IdentitySummary {
  @doc("Whether the active identity is delegated.")
  isDelegated: boolean;
}

@doc("Email verification result.")
model VerifyEmailResult {
  @doc("Verified email address.")
  email: string;
  @doc("Timestamp when the email was verified.")
  verifiedAt?: Timestamp | null;
}

@doc("Redirect URL payload used by social login endpoints.")
model RedirectUrlResult {
  @doc("Client redirect URL returned by the backend.")
  redirectUrl: string;
}

@doc("Request body for sending an auth code.")
model SendAuthCodeRequestBody {
  @doc("Email address that will receive the auth code.")
  email: string;
}

@doc("Request body for verifying an auth code.")
model VerifyEmailRequestBody {
  @doc("Email address being verified.")
  email: string;
  @doc("Auth code delivered to the email address.")
  authCode: string;
}

@doc("Request body for registering a new identity.")
model CreateIdentityRequestBody {
  @doc("Requested username.")
  username: string;
  @doc("Email address.")
  email: string;
  @doc("Plain text password.")
  password: string;
  @doc("Password confirmation.")
  confirmedPassword: string;
  @doc("Base64-encoded profile image.")
  base64EncodedImage?: string | null;
  @doc("Invitation token used during signup.")
  invitationToken?: string | null;
}

@doc("Request body for logging in.")
model LoginRequestBody {
  @doc("Email address.")
  email: string;
  @doc("Plain text password.")
  password: string;
}

@doc("Request body for switching the active identity.")
model SwitchIdentityRequestBody {
  @doc("Delegation identifier to switch into. Null means returning to the original identity.")
  targetDelegationIdentifier?: Uuid | null;
}

@doc("Response returned when an endpoint succeeds with an empty JSON object.")
model OkEmptyResponse {
  @statusCode statusCode: 200;
  @body body: EmptyJsonObject;
}

@doc("Response returned when an identity is created.")
model CreateIdentityResponse {
  @statusCode statusCode: 201;
  @body body: IdentitySummary;
}

@doc("Response returned when an identity operation succeeds.")
model OkIdentityResponse {
  @statusCode statusCode: 200;
  @body body: IdentitySummary;
}

@doc("Response returned when email verification succeeds.")
model VerifyEmailResponse {
  @statusCode statusCode: 200;
  @body body: VerifyEmailResult;
}

@doc("Response returned when identity switching succeeds.")
model SwitchIdentityResponse {
  @statusCode statusCode: 200;
  @body body: SwitchedIdentitySummary;
}

@doc("Response returned by social login endpoints.")
model RedirectUrlResponse {
  @statusCode statusCode: 200;
  @body body: RedirectUrlResult;
}

@route("/auth")
interface IdentityAuthOperations {
  @post
  @route("/send-auth-code")
  @doc("Send an auth code to the specified email address.")
  sendAuthCode(
    @body body: SendAuthCodeRequestBody,
  ): OkEmptyResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/verify-email")
  @doc("Verify an email address using the issued auth code.")
  verifyEmail(
    @body body: VerifyEmailRequestBody,
  ): VerifyEmailResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/register")
  @doc("Register a new identity.")
  createIdentity(
    @body body: CreateIdentityRequestBody,
  ): CreateIdentityResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/login")
  @doc("Authenticate with email and password.")
  login(
    @body body: LoginRequestBody,
  ): OkIdentityResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @get
  @route("/social/{provider}/redirect")
  @doc("Create an OAuth redirect URL for the selected social provider.")
  socialLoginRedirect(
    @path provider: string,
    @query accountType?: string,
    @query invitationToken?: string,
  ): RedirectUrlResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @get
  @route("/social/{provider}/callback")
  @doc("Handle the social login callback and return the client redirect URL.")
  socialLoginCallback(
    @path provider: string,
    @query code: string,
    @query state: string,
  ): RedirectUrlResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/logout")
  @doc("Log out the current authenticated identity.")
  logout(): OkEmptyResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/switch-identity")
  @doc("Switch the current authenticated identity or clear delegation.")
  switchIdentity(
    @body body: SwitchIdentityRequestBody,
  ): SwitchIdentityResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
