using TypeSpec.Http;
using KPool.Common;

@service(#{ title: "k-pool Identity API" })
@server("/api/identity", "Laravel route prefix from bootstrap/app.php.")
namespace KPool.IdentityApi;

@doc("Identity summary returned after authentication-related operations.")
model IdentitySummary {
  @doc("Identity identifier.")
  identityIdentifier: Uuid;
  @doc("IdentityName.")
  identityName: string;
  @doc("Email address.")
  email: string;
  @doc("Language code associated with the identity.")
  language: string;
  @doc("Profile image path.")
  profileImage?: string | null;
}

@doc("Single condition clause in an account policy.")
model AccountPolicyConditionClause {
  @doc("Field name to evaluate.")
  field: string;
  @doc("Operator used for the comparison.")
  operator: string;
  @doc("Value to compare against.")
  value: string | string[] | boolean;
}

@doc("Conditional constraints for an account policy statement.")
model AccountPolicyCondition {
  @doc("List of condition clauses.")
  clauses?: AccountPolicyConditionClause[];
}

@doc("Policy statement returned for account effective policies.")
model AccountPolicyStatement {
  @doc("Whether the statement allows or denies the action.")
  effect: string;
  @doc("Actions covered by this statement.")
  actions: string[];
  @doc("Resource types covered by this statement.")
  resourceTypes: string[];
  @doc("Optional condition attached to the statement.")
  condition?: AccountPolicyCondition | null;
}

@doc("Effective account policy summary for the authenticated identity.")
model AccountEffectivePolicySummary {
  @doc("Policy identifier.")
  policyIdentifier: Uuid;
  @doc("Policy name.")
  name: string;
  @doc("Whether this is a system policy.")
  isSystemPolicy: boolean;
  @doc("Statements that define the effective policy.")
  statements: AccountPolicyStatement[];
}

@doc("Account contact address summary returned for authenticated account details.")
model AuthenticatedAccountAddressSummary {
  @doc("ISO 3166-1 alpha-2 country code.")
  countryCode?: string | null;
  @doc("Administrative area code for supported countries.")
  administrativeAreaCode?: string | null;
  @doc("Postal code.")
  postalCode?: string | null;
  @doc("Locality/city name.")
  locality?: string | null;
  @doc("Primary address line.")
  addressLine1?: string | null;
  @doc("Secondary address line.")
  addressLine2?: string | null;
}

@doc("Account summary returned for the authenticated identity.")
model AuthenticatedAccountSummary {
  @doc("Account identifier.")
  accountIdentifier: Uuid;
  @doc("Account email address.")
  email: string;
  @doc("Account type.")
  type: string;
  @doc("Account display name.")
  name: string;
  @doc("Account status.")
  status: string;
  @doc("Account category assigned during creation.")
  accountCategory: string;
  @doc("Contact phone number.")
  phone?: string | null;
  @doc("Contact address.")
  address?: AuthenticatedAccountAddressSummary | null;
}

@doc("Account reference used by account switching controls.")
model AuthenticatedAccountReferenceSummary {
  @doc("Account identifier.")
  accountIdentifier: Uuid;
  @doc("Account display name.")
  name: string;
}

@doc("Account that the authenticated identity can switch to through an approved delegation.")
model SwitchableAccountSummary {
  @doc("Approved delegation identifier used by POST /accounts/switch.")
  delegationIdentifier: Uuid;
  @doc("Target account identifier.")
  accountIdentifier: Uuid;
  @doc("Target account information used for display.")
  account: AuthenticatedAccountReferenceSummary;
  @doc("Whether this delegation currently supplies the effective account context.")
  isCurrent: boolean;
}

@doc("Authenticated identity summary returned for the current identity.")
model AuthenticatedIdentitySummary extends IdentitySummary {
  @doc("Account identifier associated with the authenticated identity.")
  accountIdentifier: Uuid | null;
  @doc("Account principal identifier associated with the authenticated identity in the current account.")
  accountPrincipalIdentifier: Uuid | null;
  @doc("Account type associated with the authenticated identity.")
  accountType: string | null;
  @doc("Effective policies for the authenticated identity in the current account.")
  accountPolicies: AccountEffectivePolicySummary[];
  @doc("Detailed information for the currently effective account.")
  account?: AuthenticatedAccountSummary | null;
  @doc("Original account to return to when ending delegated account context.")
  originalAccount: AuthenticatedAccountReferenceSummary | null;
  @doc("Delegation currently supplying the effective account context.")
  delegationIdentifier: Uuid | null;
  @doc("Approved delegated accounts available from the original account.")
  switchableAccounts: SwitchableAccountSummary[];
}

@doc("Public profile fields for a requested identity.")
model IdentityProfileSummary {
  @doc("Identity identifier.")
  identityIdentifier: Uuid;
  @doc("IdentityName.")
  identityName: string;
  @doc("Language code associated with the identity.")
  language: string;
  @doc("Profile image URL.")
  profileImage?: string | null;
}

@doc("Identity summary returned after email login.")
model LoginIdentitySummary extends IdentitySummary {
  @doc("Normalized application return path after login.")
  returnTo?: string | null;
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

@doc("Request body for starting passkey registration.")
model CreatePasskeyRegistrationOptionsRequestBody {
  @doc("Verified or invited email address.")
  email: string;
  @doc("Account type retained for signup completion.")
  accountType?: string | null;
  @doc("Invitation token retained for signup completion.")
  oneTimeToken?: string | null;
  @doc("Application return path retained for signup completion.")
  return_to?: string | null;
}

model PasskeyRelyingPartyEntity {
  name: string;
  id: string;
}

model PasskeyUserEntity {
  name: string;
  id: string;
  displayName: string;
}

model PasskeyCredentialParameter {
  type: string;
  alg: int32;
}

model PasskeyCredentialDescriptor {
  type: string;
  id: string;
  transports?: string[] | null;
}

model PasskeyAuthenticatorSelection {
  residentKey: string;
  userVerification: string;
  requireResidentKey?: boolean | null;
}

@doc("WebAuthn PublicKeyCredentialCreationOptions returned to the browser.")
model PasskeyRegistrationOptions {
  rp: PasskeyRelyingPartyEntity;
  user: PasskeyUserEntity;
  challenge: string;
  pubKeyCredParams: PasskeyCredentialParameter[];
  timeout: int32;
  excludeCredentials: PasskeyCredentialDescriptor[];
  authenticatorSelection: PasskeyAuthenticatorSelection;
  attestation: string;
}

@doc("Result for starting passkey registration.")
model PasskeyRegistrationOptionsResult {
  @doc("Key used to submit the registration attestation.")
  challengeKey: Uuid;
  options: PasskeyRegistrationOptions;
}

@doc("WebAuthn PublicKeyCredentialRequestOptions returned to the browser.")
model PasskeyAuthenticationOptions {
  challenge: string;
  timeout: int32;
  rpId: string;
  allowCredentials: PasskeyCredentialDescriptor[];
  userVerification: string;
}

@doc("Result for starting passkey authentication.")
model PasskeyAuthenticationOptionsResult {
  @doc("Key used to submit the authentication assertion.")
  challengeKey: Uuid;
  options: PasskeyAuthenticationOptions;
}

model PasskeyAuthenticatorAssertionResponse {
  clientDataJSON: string;
  authenticatorData: string;
  signature: string;
  userHandle?: string | null;
}

model PasskeyAuthenticationCredential {
  id: string;
  rawId: string;
  type: "public-key";
  response: PasskeyAuthenticatorAssertionResponse;
  authenticatorAttachment?: string | null;
  clientExtensionResults?: EmptyJsonObject | null;
}

@doc("Request body for completing passkey authentication.")
model AuthenticateWithPasskeyRequestBody {
  challengeKey: Uuid;
  credential: PasskeyAuthenticationCredential;
}

@doc("Request body for registering a new identity.")
model CreateIdentityRequestBody {
  @doc("Requested identityName.")
  identityName: string;
  @doc("Email address.")
  email: string;
  @doc("Plain text password.")
  password: string;
  @doc("Password confirmation.")
  confirmedPassword: string;
  @doc("Base64-encoded profile image.")
  base64EncodedImage?: string | null;
  @doc("One-time token used during signup.")
  oneTimeToken?: string | null;
}


@doc("Request body for updating an identity profile.")
model UpdateIdentityRequestBody {
  @doc("Identity display name.")
  identityName?: string | null;
  @doc("Language code associated with the identity.")
  language?: string | null;
  @doc("Base64-encoded profile image.")
  base64EncodedImage?: string | null;
}

@doc("Request body for logging in.")
model LoginRequestBody {
  @doc("Email address.")
  email: string;
  @doc("Plain text password.")
  password: string;
  @doc("Application return path after login.")
  return_to?: string | null;
}

@doc("Response returned when an endpoint succeeds with an empty JSON object.")
model OkEmptyResponse {
  @statusCode statusCode: 200;
  @body body: EmptyJsonObject;
}

@doc("Response returned when passkey registration options are created.")
model PasskeyRegistrationOptionsResponse {
  @statusCode statusCode: 200;
  @body body: PasskeyRegistrationOptionsResult;
}

@doc("Response returned when passkey authentication options are created.")
model PasskeyAuthenticationOptionsResponse {
  @statusCode statusCode: 200;
  @body body: PasskeyAuthenticationOptionsResult;
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

@doc("Response returned when email login succeeds.")
model OkLoginResponse {
  @statusCode statusCode: 200;
  @body body: LoginIdentitySummary;
}

@doc("Response returned when fetching the current authenticated identity succeeds.")
model AuthenticatedIdentityResponse {
  @statusCode statusCode: 200;
  @body body: AuthenticatedIdentitySummary;
}

@doc("Response returned when fetching a requested identity profile succeeds.")
model IdentityProfileResponse {
  @statusCode statusCode: 200;
  @body body: IdentityProfileSummary;
}

@doc("Response returned when email verification succeeds.")
model VerifyEmailResponse {
  @statusCode statusCode: 200;
  @body body: VerifyEmailResult;
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
  @route("/passkeys/registration/options")
  @doc("Create WebAuthn registration options for a verified or invited email address.")
  createPasskeyRegistrationOptions(
    @body body: CreatePasskeyRegistrationOptionsRequestBody,
  ): PasskeyRegistrationOptionsResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/passkeys/authentication/options")
  @doc("Create discoverable WebAuthn authentication options without requiring an email address.")
  createPasskeyAuthenticationOptions(): PasskeyAuthenticationOptionsResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/passkeys/authentication")
  @doc("Verify a WebAuthn assertion and establish an authenticated session.")
  authenticateWithPasskey(
    @body body: AuthenticateWithPasskeyRequestBody,
  ): OkIdentityResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

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
  ): OkLoginResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @get
  @route("/social/{provider}/redirect")
  @doc("Create an OAuth redirect URL for the selected social provider.")
  socialLoginRedirect(
    @path provider: string,
    @query accountType?: string,
    @query oneTimeToken?: string,
    @query return_to?: string | null,
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

  @get
  @route("/me")
  @doc("Get the current authenticated identity.")
  getAuthenticatedIdentity(): AuthenticatedIdentityResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @get
  @route("/identities/{identityIdentifier}/profile")
  @doc("Get profile fields for the requested identity. Any authenticated identity may fetch the profile.")
  getIdentityProfile(
    @path identityIdentifier: Uuid,
  ): IdentityProfileResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

}

@route("/identities")
interface IdentityOperations {
  @patch
  @route("/me")
  @doc("Update the current identity profile.")
  updateIdentity(
    @body body: UpdateIdentityRequestBody,
  ): OkIdentityResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
