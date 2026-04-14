using TypeSpec.Http;

namespace KPool.AccountApi;

@doc("Verification document submitted by the applicant.")
model VerificationDocumentUploadRequestBody {
  @doc("Document type.")
  documentType: string;
  @doc("Original file name.")
  fileName: string;
  @doc("Base64-encoded file contents.")
  fileContents: string;
  @doc("Document size in bytes.")
  fileSizeBytes: int32;
}

@doc("Request body for requesting account verification.")
model RequestVerificationRequestBody {
  @doc("Account identifier under review.")
  accountIdentifier: Uuid;
  @doc("Verification type.")
  verificationType: string;
  @doc("Applicant full name.")
  applicantName: string;
  @doc("Documents submitted for review.")
  documents: VerificationDocumentUploadRequestBody[];
}

@doc("Request body for approving account verification.")
model ApproveVerificationRequestBody {
  @doc("Reviewer account identifier.")
  reviewerAccountIdentifier: Uuid;
}

@doc("Request body for rejecting account verification.")
model RejectVerificationRequestBody {
  @doc("Reviewer account identifier.")
  reviewerAccountIdentifier: Uuid;
  @doc("Rejection reason code.")
  rejectionReasonCode: string;
  @doc("Optional rejection detail.")
  rejectionReasonDetail?: string | null;
}

@doc("Response returned when an account verification is created.")
model CreateAccountVerificationResponse {
  @statusCode statusCode: 201;
  @body body: AccountVerificationSummary;
}

@doc("Response returned when an account verification operation succeeds.")
model OkAccountVerificationResponse {
  @statusCode statusCode: 200;
  @body body: AccountVerificationSummary;
}

@route("/account-verifications")
interface AccountVerificationOperations {
  @post
  @doc("Request account verification.")
  requestVerification(
    @body body: RequestVerificationRequestBody,
  ): CreateAccountVerificationResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{verificationId}/approve")
  @doc("Approve account verification.")
  approveVerification(
    @path verificationId: Uuid,
    @body body: ApproveVerificationRequestBody,
  ): OkAccountVerificationResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{verificationId}/reject")
  @doc("Reject account verification.")
  rejectVerification(
    @path verificationId: Uuid,
    @body body: RejectVerificationRequestBody,
  ): OkAccountVerificationResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
