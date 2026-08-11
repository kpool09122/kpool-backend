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

@doc("Request body for creating an account.")
model CreateAccountRequestBody {
  @doc("Account email address.")
  email: string;
  @doc("Account type.")
  accountType: string;
  @doc("Account display name.")
  accountName: string;
  @doc("Identity identifier to attach to the account.")
  principalIdentifier?: Uuid | null;
  @doc("Contact phone number.")
  phone?: string | null;
  @doc("Contact address.")
  address?: ContactAddressSummary | null;
}

@doc("Response returned when an account is created.")
model CreateAccountResponse {
  @statusCode statusCode: 201;
  @body body: CreateAccountResult;
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
  @doc("Create an account.")
  createAccount(
    @body body: CreateAccountRequestBody,
  ): CreateAccountResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

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
  ): OkAccountResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @delete
  @route("/{accountId}")
  @doc("Delete an account.")
  deleteAccount(
    @path accountId: Uuid,
  ): OkAccountResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{accountId}/documents")
  @doc("Upload required documents for an account.")
  uploadDocuments(
    @path accountId: Uuid,
    @body body: UploadAccountDocumentsRequestBody,
  ): CreatedAccountDocumentsResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @get
  @route("/{accountId}/documents/{documentType}")
  @doc("View a saved document file for the specified account as an operator.")
  viewAccountDocument(
    @path accountId: Uuid,
    @path documentType: string,
  ): OkAccountDocumentFileResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

}
