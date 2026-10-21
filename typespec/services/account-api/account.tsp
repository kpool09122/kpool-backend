using TypeSpec.Http;
using KPool.Common;

namespace KPool.AccountApi;

@doc("An account email notification recipient. Other channels are not currently supported.")
model AccountNotificationRecipient {
  identityIdentifier: Uuid;
  channel: "email";
}

model AccountNotificationCandidate {
  identityIdentifier: Uuid;
  name: string;
  email: string;
  language: string;
}

model AccountNotificationSettings {
  @doc("Selected recipients still belonging to the account, ordered by Identity UUID.")
  recipients: AccountNotificationRecipient[];
  @doc("Current account members, ordered by Identity UUID. Recipients do not need account management permissions.")
  candidates: AccountNotificationCandidate[];
  @minValue(0) @maxValue(5)
  maxRecipients: int32;
}

model UpdateAccountNotificationSettingsBody {
  @maxItems(5)
  recipients: AccountNotificationRecipient[];
}

model OkAccountNotificationSettingsResponse {
  @statusCode statusCode: 200;
  @body body: AccountNotificationSettings;
}

model UpdatedAccountNotificationSettingsResponse {
  @statusCode statusCode: 200;
  @maxItems(0) @body body: string[];
}

interface AccountNotificationOperations {
  @get @route("/accounts/{accountId}/notification-settings")
  @doc("Requires account:notification-settings:read permission: get current email recipients and candidate Identities. New accounts select their creator by default. Empty recipients disables notifications.")
  getNotificationSettings(@path accountId: Uuid): OkAccountNotificationSettingsResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | UnprocessableEntityProblemResponse | InternalServerErrorProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse;

  @put @route("/accounts/{accountId}/notification-settings")
  @doc("Requires account:notification-settings:update permission: replace all recipients. Accepts 0..min(member count,5) unique current account Identities. Only email is accepted. Empty recipients disables account-authorized email notifications.")
  updateNotificationSettings(@path accountId: Uuid, @body body: UpdateAccountNotificationSettingsBody): UpdatedAccountNotificationSettingsResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | UnprocessableEntityProblemResponse | InternalServerErrorProblemResponse | CsrfTokenMismatchProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse;
}

@doc("Request body for updating account information.")
model UpdateAccountRequestBody {
  @doc("Updated account display name.")
  accountName: string;
  @doc("Contact phone number.")
  phone?: string | null;
  @doc("Contact address.")
  address?: ContactAddressSummary | null;
}

@doc("Request body for completing initial account setup.")
model CompleteInitialSetupRequestBody {
  @doc("Selected account type.")
  accountType: "corporation" | "individual";
}

@doc("Request body for switching the current effective account context. Null switches back to the original account.")
model SwitchAccountRequestBody {
  delegationIdentifier?: Uuid | null;
}

@doc("Selected account context.")
model SwitchAccountResponseBody {
  originalIdentityIdentifier: Uuid;
  accountIdentifier: Uuid;
  accountPrincipalIdentifier: Uuid;
  delegationIdentifier?: Uuid | null;
}

@doc("Response returned when account switching succeeds.")
model SwitchAccountResponse {
  @statusCode statusCode: 200;
  @body body: SwitchAccountResponseBody;
}

@doc("Response returned when an account operation succeeds.")
model OkAccountResponse {
  @statusCode statusCode: 200;
  @body body: AccountSummary;
}

@doc("Response returned when account document upload succeeds.")
model CreatedAccountDocumentsResponse {
  @statusCode statusCode: 201;
  @body body: UploadAccountDocumentsResponseBody;
}

@doc("Response returned when account documents are listed.")
model OkAccountDocumentsResponse {
  @statusCode statusCode: 200;
  @body body: ListAccountDocumentsResponseBody;
}

@doc("Response returned when an account document file is streamed.")
model OkAccountDocumentFileResponse {
  @statusCode statusCode: 200;
  @header("Cache-Control") cacheControl: "private, no-store";
  @body body: bytes;
}

@route("/my")
interface MyAccountOperations {
  @get
  @route("/documents")
  @doc("List saved documents for the authenticated account.")
  listMyAccountDocuments(): OkAccountDocumentsResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;
}

@route("/accounts")
interface AccountOperations {
  @post
  @route("/setup")
  @doc("Select the account type and activate a pending account.")
  completeInitialSetup(
    @body body: CompleteInitialSetupRequestBody,
  ): KPool.Common.NoContentResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @get
  @route("/{accountId}")
  @doc("Get account information.")
  getAccount(
    @path accountId: Uuid,
  ): OkAccountResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @patch
  @route("/{accountId}")
  @doc("Update account information.")
  updateAccount(
    @path accountId: Uuid,
    @body body: UpdateAccountRequestBody,
  ): OkAccountResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @delete
  @route("/{accountId}")
  @doc("Delete an account.")
  deleteAccount(
    @path accountId: Uuid,
  ): OkAccountResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/switch")
  @doc("Switch the current effective account context without changing the authenticated identity.")
  switchAccount(
    @body body: SwitchAccountRequestBody,
  ): SwitchAccountResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/{accountId}/documents")
  @doc("Save the complete document set for an account, including an empty set to remove all documents.")
  uploadDocuments(
    @path accountId: Uuid,
    @body body: UploadAccountDocumentsRequestBody,
  ): CreatedAccountDocumentsResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;


}
