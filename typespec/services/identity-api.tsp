using TypeSpec.Http;
using KPool.Common;

@service(#{ title: "k-pool Identity API" })
@server("/api/v1/identity", "Laravel route prefix from bootstrap/app.php.")
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
  @doc("Account type. Null while initial setup is pending.")
  type: string | null;
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

@doc("Authentication methods available to the current identity without exposing passkey or provider-account details.")
model AuthenticationMethodsSummary {
  @doc("Number of passkeys registered by the current identity.")
  passkeyCount: int32;
  @doc("Social providers linked to the current identity.")
  linkedSocialProviders: string[];
}

model SiteManagementPolicyStatement {
  effect: string;
  actions: string[];
  resourceTypes: string[];
  condition: string | null;
}

model SiteManagementEffectivePolicySummary {
  policyIdentifier: Uuid;
  name: string;
  statements: SiteManagementPolicyStatement[];
}

@doc("Authenticated identity summary returned for the current identity.")
model AuthenticatedIdentitySummary extends IdentitySummary {
  @doc("Site management principal for the authenticated identity in the current account.")
  siteManagementPrincipalIdentifier: Uuid | null;
  @doc("Number of site management principals with an existing identity belonging to the current effective account. Pending invitations and principals from other accounts are excluded. Zero when there is no account.")
  @minValue(0)
  siteManagementPrincipalCount: int32;
  @doc("Effective site management policies for the authenticated identity in the current account.")
  siteManagementPolicies: SiteManagementEffectivePolicySummary[];
  @doc("Account identifier associated with the authenticated identity.")
  accountIdentifier: Uuid | null;
  @doc("Account principal identifier associated with the authenticated identity in the current account.")
  accountPrincipalIdentifier: Uuid | null;
  @doc("Current Account Principal status, also returned while stopped. Null only when the Principal does not exist; independent of account.status.")
  accountPrincipalStatus: PrincipalStatus | null;
  @doc("Current SiteManagement Principal status, also returned while stopped. Null only when the Principal does not exist.")
  siteManagementPrincipalStatus: PrincipalStatus | null;
  @doc("Account type associated with the authenticated identity.")
  accountType: string | null;
  @doc("Number of distinct identities belonging to the current effective account, including the current identity when it is a member. Pending invitations are excluded. Zero when there is no account.")
  @minValue(0)
  accountIdentityCount: int32;
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
  @doc("Authentication methods available to the current identity.")
  authenticationMethods: AuthenticationMethodsSummary;
}

@doc("Switchable account for an OAuth connection. Null delegationIdentifier selects the original account.")
model AuthorizedAccountCandidate {
  delegationIdentifier: Uuid | null;
  accountIdentifier: Uuid;
  account: AuthenticatedAccountReferenceSummary;
  isCurrent: boolean;
}

@doc("Account selection for an OAuth connection. Available accounts are ordered by accountIdentifier and reflect current membership and delegation permissions. An invalid selection is null, and other available accounts remain switchable.")
model AuthorizedAccountSelectionSummary {
  identityIdentifier: Uuid;
  accountIdentifier: Uuid | null;
  accountPrincipalIdentifier: Uuid | null;
  account: AuthenticatedAccountReferenceSummary | null;
  delegationIdentifier: Uuid | null;
  @doc("Original account reference only when it is explicitly granted and currently usable.")
  originalAccount: AuthenticatedAccountReferenceSummary | null;
  switchableAccounts: AuthorizedAccountCandidate[];
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

@doc("Request body for sending a passkey recovery verification code.")
model SendPasskeyRecoveryEmailRequestBody {
  @doc("Registered email address that will receive the recovery code when it exists.")
  email: string;
}

@doc("Request body for verifying a passkey recovery email code.")
model VerifyPasskeyRecoveryEmailRequestBody {
  @doc("Email address used to request the recovery code.")
  email: string;
  @doc("Six-digit recovery code delivered to the email address.")
  @minLength(6)
  @maxLength(6)
  authCode: string;
}

@doc("Result of successful passkey recovery identity verification.")
model PasskeyRecoveryVerificationResult {
  @doc("Short-lived, one-time key authorizing passkey recovery registration.")
  recoveryKey: Uuid;
}

@doc("Request body for creating passkey recovery registration options.")
model CreatePasskeyRecoveryOptionsRequestBody {
  @doc("Short-lived passkey recovery authorization key.")
  recoveryKey: Uuid;
}

@doc("Request body for starting passkey registration.")
model CreatePasskeyRegistrationOptionsRequestBody {
  @doc("Verified or invited email address.")
  email: string;
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

@doc("Public metadata for a passkey registered by the authenticated identity.")
model PasskeySummary {
  @doc("Application identifier for the registered passkey.")
  passkeyIdentifier: Uuid;
  @doc("User-visible name for the passkey.")
  displayName: string;
  @doc("Authenticator transports reported at registration.")
  transports: string[];
  @doc("Whether the credential is eligible for backup and synchronization.")
  backupEligible: boolean;
  @doc("Whether the credential is currently backed up.")
  backupState: boolean;
  @doc("Timestamp of the most recent successful authentication.")
  lastUsedAt: Timestamp | null;
  @doc("Timestamp when the passkey was registered.")
  createdAt: Timestamp;
}

@doc("Registered passkeys belonging to the authenticated identity.")
model PasskeyListResult {
  passkeys: PasskeySummary[];
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

model PasskeyAuthenticatorAttestationResponse {
  clientDataJSON: string;
  attestationObject: string;
  transports?: string[] | null;
}

model PasskeyRegistrationCredential {
  id: string;
  rawId: string;
  type: "public-key";
  response: PasskeyAuthenticatorAttestationResponse;
  authenticatorAttachment?: string | null;
  clientExtensionResults?: EmptyJsonObject | null;
}

@doc("Request body for completing passkey-based identity registration.")
model RegisterWithPasskeyRequestBody {
  challengeKey: Uuid;
  @doc("Requested identity name.")
  @minLength(1)
  @maxLength(32)
  identityName: string;
  @minLength(1)
  @maxLength(64)
  @doc("User-visible name for the passkey.")
  displayName: string;
  @doc("Base64-encoded profile image.")
  base64EncodedImage?: string | null;
  credential: PasskeyRegistrationCredential;
}

@doc("Identity and retained navigation state returned after passkey registration.")
model PasskeyIdentityRegistrationResult extends IdentitySummary {
  returnTo?: string | null;
}

@doc("Request body for completing registration of an additional passkey.")
model AddPasskeyRequestBody {
  challengeKey: Uuid;
  @maxLength(64)
  displayName: string;
  credential: PasskeyRegistrationCredential;
}

@doc("Request body for replacing all existing passkeys during account recovery.")
model RecoverPasskeyRequestBody {
  @doc("Short-lived passkey recovery authorization key.")
  recoveryKey: Uuid;
  @doc("Key returned with the recovery registration options.")
  challengeKey: Uuid;
  @minLength(1)
  @maxLength(64)
  @doc("User-visible name for the replacement passkey.")
  displayName: string;
  credential: PasskeyRegistrationCredential;
}

@doc("Request body for updating a registered passkey.")
model UpdatePasskeyRequestBody {
  @minLength(1)
  @maxLength(64)
  @doc("New user-visible name for the passkey.")
  displayName: string;
}

@doc("Request body for completing passkey authentication.")
model AuthenticateWithPasskeyRequestBody {
  challengeKey: Uuid;
  credential: PasskeyAuthenticationCredential;
}

@doc("Request body for completing passkey step-up authentication.")
model CompleteStepUpWithPasskeyRequestBody {
  challengeKey: Uuid;
  credential: PasskeyAuthenticationCredential;
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

@doc("Empty JSON array returned by endpoints with no payload.")
@maxItems(0)
model EmptyJsonArray is Array<unknown>;

@doc("Response returned when an endpoint succeeds with an empty JSON array.")
model OkEmptyResponse {
  @statusCode statusCode: 200;
  @body body: EmptyJsonArray;
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

@doc("Response returned when registered passkeys are listed.")
model PasskeyListResponse {
  @statusCode statusCode: 200;
  @body body: PasskeyListResult;
}

@doc("Response returned when passkey-based identity registration succeeds.")
model PasskeyIdentityRegistrationResponse {
  @statusCode statusCode: 201;
  @body body: PasskeyIdentityRegistrationResult;
}

@doc("Response returned when an identity operation succeeds.")
model OkIdentityResponse {
  @statusCode statusCode: 200;
  @body body: IdentitySummary;
}

@doc("Response returned when fetching the current authenticated identity succeeds.")
model AuthenticatedIdentityResponse {
  @statusCode statusCode: 200;
  @doc("Cookie authentication returns the identity summary; OAuth Bearer authentication returns only the account selection for the connection.")
  @body body: AuthenticatedIdentitySummary | AuthorizedAccountSelectionSummary;
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

@doc("Response returned after passkey recovery identity verification succeeds.")
model PasskeyRecoveryVerificationResponse {
  @statusCode statusCode: 200;
  @body body: PasskeyRecoveryVerificationResult;
}

@doc("Response returned by social login endpoints.")
model RedirectUrlResponse {
  @statusCode statusCode: 200;
  @body body: RedirectUrlResult;
}

@doc("Pending SSO addition, available only in the Laravel session that completed OAuth. No login or linking has occurred yet.")
model SocialLinkingResult {
  @doc("Authenticated SSO provider being added. The provider account ID remains server-side.")
  provider: string;
  @doc("Registered email address receiving the dedicated linking code; cannot be changed by the client.")
  email: string;
  @doc("Absolute expiry of the pending linking operation. Restart OAuth after expiry.")
  expiresAt: Timestamp;
}

model SocialLinkingResponse {
  @statusCode statusCode: 200;
  @body body: SocialLinkingResult;
}

@doc("Common email-send state returned without revealing whether an account exists or whether delivery completed.")
model EmailSendingStatusResult {
  @doc("The valid request was processed. Cooldown or rate-limited requests also return true.")
  accepted: boolean;
  @doc("Number of sends remaining in the applicable limit after this request. Social linking returns the smaller of the hourly and operation budgets.")
  remainingSends: int32;
  @doc("Seconds until another send can be attempted. Zero means immediately available. Null means the social-linking operation cannot send again because its operation limit or expiry was reached.")
  retryAfterSeconds: int32 | null;
}

@doc("200 response containing the common email-send state. Example after the first allowed send: accepted=true, remainingSends=4, retryAfterSeconds=60.")
model EmailSendingStatusResponse {
  @statusCode statusCode: 200;
  @body body: EmailSendingStatusResult;
}

model VerifySocialLinkingEmailRequestBody {
  @doc("Dedicated six-digit SSO linking code. Registration and passkey recovery codes cannot authorize linking.")
  @pattern("^[0-9]{6}$")
  authCode: string;
}

@doc("Actual browser redirect returned by the OAuth callback.")
model SocialCallbackRedirectResponse {
  @statusCode statusCode: 302;
  @header location: string;
}

@route("/auth")
interface IdentityAuthOperations {
  @get
  @route("/csrf-token")
  @doc("Safely initialize browser CSRF protection without requiring login. Laravel issues XSRF-TOKEN and the session cookie using Set-Cookie; the empty response is not cacheable. Keep both cookies and send the URL-decoded XSRF-TOKEN cookie value in X-XSRF-TOKEN for session API mutations. This endpoint changes no identity or account data. Use these cookies and the X-XSRF-TOKEN header for all session API mutations, including login and signup.")
  getCsrfToken(): KPool.Common.NoContentResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @get
  @route("/social/link")
  @doc("Read pending SSO linking confirmation using the originating Laravel session cookie. Returns 422 when absent, expired, consumed, or accessed from another session. No provider, identity, email, return path or pending key is accepted from the client.")
  getSocialLinking(): SocialLinkingResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/social/link/email")
  @doc("Request a dedicated code for the pending target's registered email using the same session cookie. No request body is required. Codes expire with the ten-minute pending operation; a successful resend invalidates the old code without resetting failed attempts. A 60-second cooldown and a maximum of five sends per pending operation and per target identity per hour apply; throttled requests return accepted=true without a new code. Invalid or expired pending operations return 422.")
  sendSocialLinkingEmail(): EmailSendingStatusResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/social/link/email/verification")
  @doc("Verify the dedicated code and consume pending authorization in the originating Laravel session. Re-fetch and check the target and SSO ownership, save the addition and log in with a regenerated session ID within an Action-managed database transaction. Returns an allowed frontend return path. Invalid codes, expiry, five failed attempts, reuse and linking conflicts return 422. A save failure never logs in; the authorization remains consumed and OAuth must be restarted before retrying.")
  verifySocialLinkingEmail(
    @body body: VerifySocialLinkingEmailRequestBody,
  ): RedirectUrlResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/send-auth-code")
  @doc("Send an auth code to the specified email address.")
  sendAuthCode(
    @body body: SendAuthCodeRequestBody,
  ): EmailSendingStatusResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/verify-email")
  @doc("Verify an email address using the issued auth code.")
  verifyEmail(
    @body body: VerifyEmailRequestBody,
  ): VerifyEmailResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/passkeys/registration/options")
  @doc("Create WebAuthn registration options for a verified or invited email address.")
  createPasskeyRegistrationOptions(
    @body body: CreatePasskeyRegistrationOptionsRequestBody,
  ): PasskeyRegistrationOptionsResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/passkeys/registration")
  @doc("Verify a WebAuthn attestation, create an identity and establish an authenticated session.")
  registerWithPasskey(
    @body body: RegisterWithPasskeyRequestBody,
  ): PasskeyIdentityRegistrationResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/passkeys/authentication/options")
  @doc("Create discoverable WebAuthn authentication options without requiring an email address.")
  createPasskeyAuthenticationOptions(): PasskeyAuthenticationOptionsResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/passkeys/authentication")
  @doc("Verify a WebAuthn assertion and establish an authenticated session.")
  authenticateWithPasskey(
    @body body: AuthenticateWithPasskeyRequestBody,
  ): OkIdentityResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/passkeys/recovery/email")
  @doc("Send a passkey recovery code while returning the same response whether or not the email is registered.")
  sendPasskeyRecoveryEmail(
    @body body: SendPasskeyRecoveryEmailRequestBody,
  ): EmailSendingStatusResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/passkeys/recovery/email/verification")
  @doc("Verify a passkey recovery email code and issue a short-lived recovery key.")
  verifyPasskeyRecoveryEmail(
    @body body: VerifyPasskeyRecoveryEmailRequestBody,
  ): PasskeyRecoveryVerificationResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @get
  @route("/passkeys/recovery/social/{provider}/redirect")
  @doc("Create a social reauthentication URL. Identify the recovery target from the linked social account on callback.")
  startPasskeyRecoveryWithSocial(
    @path provider: string,
  ): RedirectUrlResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/passkeys/recovery/options")
  @doc("Create WebAuthn registration options authorized by a passkey recovery key.")
  createPasskeyRecoveryOptions(
    @body body: CreatePasskeyRecoveryOptionsRequestBody,
  ): PasskeyRegistrationOptionsResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/passkeys/recovery")
  @doc("Register a replacement passkey, remove all previous passkeys, and invalidate existing login sessions.")
  recoverPasskey(
    @body body: RecoverPasskeyRequestBody,
  ): KPool.Common.NoContentResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @get
  @route("/social/{provider}/redirect")
  @doc("Create an OAuth redirect URL for the selected social provider.")
  socialLoginRedirect(
    @path provider: string,
    @query oneTimeToken?: string,
    @query return_to?: string | null,
  ): RedirectUrlResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @get
  @route("/social/{provider}/callback")
  @doc("Handle OAuth and redirect the browser. An unlinked provider whose email matches an existing identity redirects to the frontend /auth/social/link confirmation screen, retaining server-side pending data for ten minutes in the originating Laravel session. No linking or login occurs before dedicated email verification. Linked login, new identity, invitation, step-up and passkey recovery flows retain their existing redirects.")
  socialLoginCallback(
    @path provider: string,
    @query code: string,
    @query state: string,
  ): SocialCallbackRedirectResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/logout")
  @doc("Log out the current authenticated identity.")
  logout(): OkEmptyResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @get
  @route("/me")
  @useAuth(SessionCookieAuth | InternalApiBearerAuth)
  @doc("Get the current authenticated identity. Internal API Bearer is an Account-selection helper with no additional scope.")
  getAuthenticatedIdentity(): AuthenticatedIdentityResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse | KPool.Common.ForbiddenProblemResponse;

  @get
  @route("/passkeys")
  @doc("List passkeys registered by the current authenticated identity after recent authentication.")
  listPasskeys(): PasskeyListResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/step-up/passkey/options")
  @doc("Create identity-bound WebAuthn options for common recent authentication.")
  createStepUpPasskeyOptions(): PasskeyAuthenticationOptionsResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/step-up/passkey")
  @doc("Verify an existing passkey and grant common recent authentication to the same identity and login session for ten minutes from verification. Passkey operations, identity withdrawal, external connection management and authorization approval share this result without consuming it or extending its expiry.")
  completeStepUpWithPasskey(
    @body body: CompleteStepUpWithPasskeyRequestBody,
  ): OkEmptyResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @get
  @route("/step-up/social/{provider}/redirect")
  @doc("Create a linked-SSO recent authentication URL when no passkey is registered. Persist the allowlisted return destination in the originating login session on the server. The result is reusable for ten minutes by the same identity and login session without consumption or extension.")
  startStepUpWithSocial(
    @path provider: string,
    @query returnTo: "passkeys" | "withdrawal" | "external-connections" | "authorization",
  ): RedirectUrlResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/passkeys/addition/options")
  @doc("Create WebAuthn registration options for adding a passkey to the authenticated identity.")
  createPasskeyOptions(): PasskeyRegistrationOptionsResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/passkeys/addition")
  @doc("Verify and register an additional passkey for the authenticated identity.")
  addPasskey(
    @body body: AddPasskeyRequestBody,
  ): OkEmptyResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @patch
  @route("/passkeys/{passkeyIdentifier}")
  @doc("Update a passkey belonging to the authenticated identity after recent authentication.")
  updatePasskey(
    @path passkeyIdentifier: Uuid,
    @body body: UpdatePasskeyRequestBody,
  ): OkEmptyResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @delete
  @route("/passkeys/{passkeyIdentifier}")
  @doc("Delete a passkey belonging to the authenticated identity while preserving another authentication method.")
  deletePasskey(
    @path passkeyIdentifier: Uuid,
  ): OkEmptyResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @get
  @route("/identities/{identityIdentifier}/profile")
  @doc("Get profile fields for the requested identity. Any authenticated identity may fetch the profile.")
  getIdentityProfile(
    @path identityIdentifier: Uuid,
  ): IdentityProfileResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

}

@doc("Confirmation of the current authenticated identity name for withdrawal.")
model WithdrawFromServiceRequestBody {
  @minLength(1)
  @maxLength(32)
  confirmationIdentityName: string;
}

model WithdrawalEligibility {
  canWithdraw: boolean;
}

@route("/identities")
interface IdentityOperations {
  @get
  @route("/me/withdrawal-eligibility")
  @doc("Check self-service withdrawal eligibility against every actual membership using the same rules as withdrawal. Any corporate membership whose account email matches the identity email case-insensitively blocks self-service withdrawal regardless of role. Does not require recent authentication and does not mutate data.")
  getWithdrawalEligibility(): WithdrawalEligibility | KPool.Common.UnauthorizedProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @delete
  @route("/me")
  @doc("Permanently withdraw from the service and delete the authenticated identity. Laravel CSRF protection is required for this route, including POST method override; first GET /api/v1/identity/auth/csrf-token, retain its cookies and send the URL-decoded XSRF-TOKEN cookie in X-XSRF-TOKEN (419 csrf_token_mismatch on failure). Post-commit cleanup and outer session-save failures are logged and do not change the committed 204 result. Requires reusable recent authentication from the same identity and login session within ten minutes, shared with passkey operations without consumption or extension. GENERAL individual accounts (including Owners) and GENERAL corporate non-Owners are eligible only when no corporate account email matches the identity email case-insensitively, regardless of role. Archives contain allowlisted internal identifiers and metadata only. Corporate accounts remain; individual accounts are archived and deleted. All login sessions are invalidated after commit. Requires confirmationIdentityName to exactly match the current stored identity name, including whitespace and case, before any withdrawal side effect. Missing or invalid confirmation returns 422; mismatch returns 422 identity_name_confirmation_mismatch. No target identity identifier is accepted. 401 code is authentication_required for missing login or recent_authentication_required for missing recent authentication; 403 code is identity_withdrawal_not_allowed.")
  withdrawFromService(
    @body body: WithdrawFromServiceRequestBody,
  ): KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.NoContentResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @patch
  @route("/me")
  @doc("Update the current identity profile.")
  updateIdentity(
    @body body: UpdateIdentityRequestBody,
  ): OkIdentityResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;
}
model BadRequestProblemResponse { @statusCode statusCode: 400; @body body: ProblemDetails; }

@doc("OAuth operation ceilings, never a grant of the user's business permissions. Draft write covers normal creation/editing, not automatic creation.")
union ConsentScope {
  "wiki:read", "wiki-drafts:read", "wiki-drafts:write", "wiki-drafts:submit", "wiki-images:read", "wiki-images:write",
}

@doc("Validated authorization request. Start from the frontend consent URL's OAuth parameters, converting snake_case OAuth parameters to these fields. The server requires exact registered redirect URI, configured resource, response_type=code and PKCE S256. Unknown scopes are rejected.")
model CreateAuthorizationRequestBody {
  clientIdentifier: Uuid;
  redirectUri: string;
  scopes: ConsentScope[];
  resource: string;
  codeChallenge: string;
  codeChallengeMethod: "S256";
  responseType: "code";
  state: string;
}

@doc("Opaque request handle bound at creation to the authenticated Identity and browser session. It expires after ten minutes. Use it to fetch consent details and to approve or cancel that request.")
model PendingAuthorizationResult {
  requestIdentifier: string;
  expiresAt: Timestamp;
}

model AuthorizationRequestResult {
  ...PendingAuthorizationResult;
  clientName: string;
  @doc("Requested scopes intersected with the Identity-wide allowed scopes, in requested order. Empty means consent is unavailable; disable approval and allow cancellation. Settings are rechecked on final approval.")
  scopes: { scope: ConsentScope; description: string; }[];
}

model ApproveAuthorizationBody {
  @doc("Nonempty unique subset of both the server-stored requested scopes and the latest Identity-wide allowed scopes. Expansion or disallowed scopes are rejected with 400, including settings changes after screen display. wiki-drafts:write is displayed as Draft Wiki の作成・編集・削除・取り下げ and excludes automatic creation.")
  scopes: ConsentScope[];
}

@doc("Navigate to this server-generated, registered OAuth client URI. Approval carries code and original state; denial carries error=access_denied and original state. Never expose access or refresh tokens to the consent screen. Invalid requests are local errors and must not redirect to unvalidated input.")
model AuthorizationContinuationResult { redirectUri: string; }
model CreatedAuthorizationRequestResponse { @statusCode statusCode: 201; @body body: PendingAuthorizationResult; }
model AuthorizationRequestResponse { @statusCode statusCode: 200; @body body: AuthorizationRequestResult; }
model AuthorizationContinuationResponse { @statusCode statusCode: 200; @body body: AuthorizationContinuationResult; }

@route("/oauth/requests")
@doc("Authenticated consent API with cookie/session and CSRF protection. Responses are no-store. An expired or completed request returns 409 with code oauth_request_expired or oauth_request_completed. An unknown or foreign request returns 404. Account or permission loss returns 403. Invalid consent returns 400. Restart after expiry or completion and ask for consent again. Frontend login/consent UI belongs to kpool-frontend#631/#632.")
interface OAuthConsent {
  @post
  @doc("Validate and save the request for the signed-in Identity, replacing any previous request in the same browser session. If login is required, set the frontend login page's returnTo to the local consent-page URL, preserving the original OAuth query. Use the OAuth client's redirect_uri only when returning the authorization result. After login, obtain a fresh token from /auth/csrf-token and forward the session cookies. Create the request, then GET its details.")
  @useAuth(SessionCookieAuth)
  createAuthorizationRequest(@body body: CreateAuthorizationRequestBody): CreatedAuthorizationRequestResponse | UnauthorizedProblemResponse | BadRequestProblemResponse | NotFoundProblemResponse | UnprocessableEntityProblemResponse | CsrfTokenMismatchProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse | InternalServerErrorProblemResponse;

  @get @route("/{requestIdentifier}")
  @doc("Read consent-screen details for the Identity and session that created the request. Show that the approved scopes apply to every account the user can switch to now or in the future. The selected account and current business permissions apply on every request.")
  @useAuth(SessionCookieAuth)
  getAuthorizationRequest(@path requestIdentifier: string): AuthorizationRequestResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | BadRequestProblemResponse | ConflictProblemResponse | NotFoundProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse | InternalServerErrorProblemResponse;

  @post @route("/{requestIdentifier}/approve")
  @doc("Requires reusable recent authentication within ten minutes (401 recent_authentication_required). Revalidate the original account and principal, persist scope consent and issue a code in one DB transaction. Failure rolls back consent and leaves the request retryable. Only server-stored Client/redirect/resource/PKCE/state and an approved subset of stored scopes reach code issuance.")
  @useAuth(SessionCookieAuth)
  approveAuthorization(@path requestIdentifier: string, @body body: ApproveAuthorizationBody): AuthorizationContinuationResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | BadRequestProblemResponse | ConflictProblemResponse | NotFoundProblemResponse | UnprocessableEntityProblemResponse | CsrfTokenMismatchProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse | InternalServerErrorProblemResponse;

  @post @route("/{requestIdentifier}/deny")
  @doc("Reject without storing consent or issuing a code; complete the request and return access_denied to the freshly validated registered redirect URI.")
  @useAuth(SessionCookieAuth)
  denyAuthorization(@path requestIdentifier: string): AuthorizationContinuationResponse | UnauthorizedProblemResponse | BadRequestProblemResponse | ConflictProblemResponse | NotFoundProblemResponse | CsrfTokenMismatchProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse | InternalServerErrorProblemResponse;
}

@doc("An Identity-owned external authorization connection. Scopes are operation ceilings, not Account permissions. createdAt is the consent timestamp; revokedAt is null while active. Account availability is evaluated on each API call.")
model AuthorizationConnection {
  connectionIdentifier: Uuid;
  clientIdentifier: Uuid;
  clientName: string;
  scopes: ConsentScope[];
  createdAt: Timestamp;
  revokedAt: Timestamp | null;
}

model AuthorizationConnectionsResponse {
  @statusCode statusCode: 200;
  @body body: { connections: AuthorizationConnection[]; };
}

@doc("Identity-wide ceiling for future external client consents. A missing setting allows all currently defined scopes. An explicitly saved empty list disables new consent. Existing connections and tokens are unchanged.")
model AuthorizationScopeSettings {
  @doc("Unique defined scopes. Empty is allowed. Applies to all OAuth clients for the authenticated Identity.")
  @maxItems(6)
  scopes: ConsentScope[];
}

model AuthorizationScopeSettingsResponse {
  @statusCode statusCode: 200;
  @body body: AuthorizationScopeSettings;
}

@doc("Reading or updating settings requires reusable recent authentication from the same identity and login session within ten minutes; otherwise returns 401 recent_authentication_required.")
@route("/oauth/scope-settings")
@useAuth(SessionCookieAuth)
interface OAuthScopeSettings {
  @get
  @doc("Requires reusable recent authentication from the same identity and login session within ten minutes; otherwise returns 401 recent_authentication_required. Read the authenticated Identity's allowed scopes. No target Identity ID is accepted. Cookie-only, no-store, independent of Account selection/status.")
  getAuthorizationScopeSettings(): AuthorizationScopeSettingsResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse | InternalServerErrorProblemResponse;

  @put
  @doc("Requires reusable recent authentication from the same identity and login session within ten minutes; otherwise returns 401 recent_authentication_required. Replace the authenticated Identity's allowed scopes. Cookie and CSRF required; no-store. scopes is required, unique and may be empty. Returns an empty JSON array. Does not modify existing consents; applies when subsequent consent is finalized.")
  updateAuthorizationScopeSettings(@body body: AuthorizationScopeSettings): OkEmptyResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | BadRequestProblemResponse | UnprocessableEntityProblemResponse | CsrfTokenMismatchProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse | InternalServerErrorProblemResponse;
}

@doc("Listing or revoking connections requires reusable recent authentication from the same identity and login session within ten minutes; otherwise returns 401 recent_authentication_required.")
@route("/oauth/connections")
@useAuth(SessionCookieAuth)
interface OAuthConnections {
  @get
  @doc("Requires reusable recent authentication from the same identity and login session within ten minutes; otherwise returns 401 recent_authentication_required. List only the signed-in Identity's connections, including revoked connections, ordered by consent timestamp ascending then connection ID ascending. Empty results use connections: []. Cookie-only; no-store; independent of selected Account status.")
  listAuthorizationConnections(): AuthorizationConnectionsResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse | InternalServerErrorProblemResponse;

  @delete @route("/{connectionIdentifier}")
  @doc("Requires reusable recent authentication from the same identity and login session within ten minutes; otherwise returns 401 recent_authentication_required. Revoke only the signed-in Identity's specified connection, its access/refresh tokens and authorization codes. After commit, invalidate all associated internal API authorization caches and this connection's Account selection/context, preserving other connections and browser selections. Repeating revocation succeeds without changing revokedAt. Unknown and foreign IDs both return 404. Cookie and CSRF required. Returns an empty JSON array.")
  revokeAuthorization(@path connectionIdentifier: Uuid): OkEmptyResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | NotFoundProblemResponse | UnprocessableEntityProblemResponse | CsrfTokenMismatchProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse | InternalServerErrorProblemResponse;
}
