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

@doc("Request body for beginning passkey signup.")
model BeginPasskeySignupRequestBody {
  identityName: string;
  email: string;
  language: string;
  base64EncodedImage?: string | null;
  oneTimeToken?: string | null;
}

@doc("Browser WebAuthn credential response.")
model PasskeyCredentialResponse {
  type: string;
  id: string;
  rawId: string;
  response: Record<unknown>;
  clientExtensionResults?: Record<unknown>;
}

model FinishPasskeyRegistrationRequestBody {
  challengeIdentifier: Uuid;
  credential: PasskeyCredentialResponse;
  displayName: string;
}

model FinishPasskeyLoginRequestBody {
  challengeIdentifier: Uuid;
  credential: PasskeyCredentialResponse;
}

model PasskeyOptionsResult {
  challengeIdentifier: Uuid;
  publicKey: Record<unknown>;
}

model PasskeySummary {
  passkeyIdentifier: Uuid;
  displayName: string;
  transports: string[];
  backupEligible: boolean;
  backupState: boolean;
  lastUsedAt?: Timestamp | null;
  createdAt?: Timestamp | null;
}

model PasskeyListResult {
  passkeys: PasskeySummary[];
}

model RenamePasskeyRequestBody {
  displayName: string;
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

@doc("Response returned when passkey login succeeds.")
model OkLoginResponse {
  @statusCode statusCode: 200;
  @body body: IdentitySummary;
}

model PasskeyOptionsResponse {
  @statusCode statusCode: 200;
  @body body: PasskeyOptionsResult;
}

model PasskeyListResponse {
  @statusCode statusCode: 200;
  @body body: PasskeyListResult;
}

model CreatedPasskeyResponse {
  @statusCode statusCode: 201;
  @body body: PasskeySummary;
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
  @route("/passkey/register/options")
  @doc("Create registration options for passkey signup after email verification.")
  beginPasskeySignup(
    @body body: BeginPasskeySignupRequestBody,
  ): PasskeyOptionsResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/passkey/register/verify")
  @doc("Verify attestation and create an identity with its first passkey.")
  finishPasskeySignup(
    @body body: FinishPasskeyRegistrationRequestBody,
  ): CreateIdentityResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/passkey/login/options")
  @doc("Create discoverable-credential authentication options.")
  beginPasskeyLogin(): PasskeyOptionsResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/passkey/login/verify")
  @doc("Verify a passkey assertion and establish the login session.")
  finishPasskeyLogin(
    @body body: FinishPasskeyLoginRequestBody,
  ): OkLoginResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @get
  @route("/passkeys")
  @doc("List passkeys registered for the authenticated identity.")
  listPasskeys(): PasskeyListResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/passkeys/options")
  @doc("Create registration options for an additional passkey.")
  beginAddPasskey(): PasskeyOptionsResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/passkeys/verify")
  @doc("Verify and store an additional passkey.")
  finishAddPasskey(
    @body body: FinishPasskeyRegistrationRequestBody,
  ): CreatedPasskeyResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @patch
  @route("/passkeys/{passkeyIdentifier}")
  @doc("Rename a passkey.")
  renamePasskey(
    @path passkeyIdentifier: Uuid,
    @body body: RenamePasskeyRequestBody,
  ): OkEmptyResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @delete
  @route("/passkeys/{passkeyIdentifier}")
  @doc("Delete a passkey without removing the identity's final login method.")
  deletePasskey(
    @path passkeyIdentifier: Uuid,
  ): OkEmptyResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

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
