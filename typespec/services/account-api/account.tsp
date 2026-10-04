using TypeSpec.Http;
using KPool.Common;

namespace KPool.AccountApi;

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
  listMyAccountDocuments(): OkAccountDocumentsResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}

@route("/accounts")
interface AccountOperations {
  @post
  @route("/setup")
  @doc("Select the account type and activate a pending account.")
  completeInitialSetup(
    @body body: CompleteInitialSetupRequestBody,
  ): KPool.Common.NoContentResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse;

  @get
  @route("/{accountId}")
  @doc("Get account information.")
  getAccount(
    @path accountId: Uuid,
  ): OkAccountResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @patch
  @route("/{accountId}")
  @doc("Update account information.")
  updateAccount(
    @path accountId: Uuid,
    @body body: UpdateAccountRequestBody,
  ): OkAccountResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse;

  @delete
  @route("/{accountId}")
  @doc("Delete an account.")
  deleteAccount(
    @path accountId: Uuid,
  ): OkAccountResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse;

  @post
  @route("/switch")
  @doc("Switch the current effective account context without changing the authenticated identity.")
  switchAccount(
    @body body: SwitchAccountRequestBody,
  ): SwitchAccountResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse;

  @post
  @route("/{accountId}/documents")
  @doc("Upload required documents for an account.")
  uploadDocuments(
    @path accountId: Uuid,
    @body body: UploadAccountDocumentsRequestBody,
  ): CreatedAccountDocumentsResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse;

  @get
  @route("/{accountId}/documents/{documentType}")
  @doc("View a saved document file for the specified account as an operator.")
  viewAccountDocument(
    @path accountId: Uuid,
    @path documentType: string,
  ): OkAccountDocumentFileResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

}
