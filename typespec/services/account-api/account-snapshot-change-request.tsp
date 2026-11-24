using TypeSpec.Http;
using KPool.Common;
namespace KPool.AccountApi;

@doc("Independent snapshot-change review. Original snapshot and historic service/category application references never change. Approval and rejection both preserve a snapshot with the corresponding status; only approved snapshots can become current.")
model AccountSnapshotChangeRequestSummary {
  requestIdentifier: Uuid;
  accountIdentifier: Uuid;
  snapshotIdentifier: Uuid;
  reviewedSnapshotIdentifier: Uuid | null;
  @minLength(1) @maxLength(2000) changeReason: string;
  status: "pending" | "approved" | "rejected";
  requestedAt: utcDateTime;
  reviewedBy: Uuid | null;
  reviewedAt: utcDateTime | null;
  rejectionReason: {code: string; detail: string | null;} | null;
}
model AccountSnapshotChangeProposal {
  account: AccountSnapshotProfile;
  documents: AccountSnapshotDocument[];
}
model AccountSnapshotChangeRequestDetail {
  request: AccountSnapshotChangeRequestSummary;
  original: AccountSnapshotDetail;
  proposal: AccountSnapshotChangeProposal;
}
model RequestAccountSnapshotChangeBody {
  @minLength(1) @maxLength(2000) changeReason: string;
}
model CreatedAccountSnapshotChangeRequest {
  @statusCode statusCode: 201;
  @body body: AccountSnapshotChangeRequestSummary;
}
model OkAccountSnapshotChangeRequest {
  @statusCode statusCode: 200;
  @body body: AccountSnapshotChangeRequestSummary;
}
model OkAccountSnapshotChangeRequestDetail {
  @statusCode statusCode: 200;
  @body body: AccountSnapshotChangeRequestDetail;
}
model OkLatestAccountSnapshotChangeRequest {
  @statusCode statusCode: 200;
  @body body: {
    request: AccountSnapshotChangeRequestSummary | null;
    @doc("True when an approved category application exists for this Account. Remains true after snapshot-change approval; identical-content normal saves remain no-op successes. Use this field to choose the change-application flow, and request.status to display its review state.")
    changeRequiresApproval: boolean;
  };
}
model OkAccountSnapshotChangeRequestList {
  @statusCode statusCode: 200;
  @body body: {
    requests: AccountSnapshotChangeRequestSummary[];
    pagination: {currentPage: int32; lastPage: int32; total: int32; perPage: int32;};
  };
}
interface AccountSnapshotChangeRequestOperations {
  @post @route("/accounts/{accountIdentifier}/snapshot-change-requests")
  @doc("Submit a required reason and freeze the current editable Account profile and uploaded documents after the same complete-contact/type/required-document validation as normal snapshot save. Requires same Account and account:update. A saved original snapshot must exist (404 otherwise). While pending, profile updates, document uploads and normal snapshot saves return 403. Review reads the current Account and documents without a separate copy. No snapshot is created on submission; duplicate pending applications return 409. Rejected/approved history permits a new application, independently of category-change request states.")
  requestAccountSnapshotChange(@path accountIdentifier: Uuid, @body body: RequestAccountSnapshotChangeBody): CreatedAccountSnapshotChangeRequest | UnauthorizedProblemResponse | ForbiddenProblemResponse | NotFoundProblemResponse | ConflictProblemResponse | UnprocessableEntityProblemResponse | InternalServerErrorProblemResponse | CsrfTokenMismatchProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse;

  @get @route("/accounts/{accountIdentifier}/snapshot-change-requests/latest")
  @doc("Same Account and account:read required. Latest request ordered by requestedAt descending, then identifier descending, or null. changeRequiresApproval is derived from approved category applications, not snapshot-local flags.")
  getLatestAccountSnapshotChangeRequest(@path accountIdentifier: Uuid): OkLatestAccountSnapshotChangeRequest | UnauthorizedProblemResponse | ForbiddenProblemResponse | NotFoundProblemResponse | UnprocessableEntityProblemResponse | InternalServerErrorProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse;

  @get @route("/account-snapshot-change-requests")
  @doc("Operations-only list. Requires account:snapshot-change-request:manage on the operator Account. Ordered by requestedAt descending then identifier descending. Optional review status filter, bounded pagination (default 50, maximum 100).")
  listAccountSnapshotChangeRequests(@query status?: "pending" | "approved" | "rejected" | null, @query perPage?: int32 | null, @query page?: int32 | null): OkAccountSnapshotChangeRequestList | UnauthorizedProblemResponse | ForbiddenProblemResponse | NotFoundProblemResponse | UnprocessableEntityProblemResponse | InternalServerErrorProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse;

  @get @route("/account-snapshot-change-requests/{requestId}")
  @doc("Compare the pinned original with the current Account while pending, or the reviewed snapshot after approval or rejection. Same Account requires account:read; cross-Account access requires account:snapshot-change-request:manage on the operator Account. Returns no storage paths or hashes.")
  getAccountSnapshotChangeRequest(@path requestId: Uuid): OkAccountSnapshotChangeRequestDetail | UnauthorizedProblemResponse | ForbiddenProblemResponse | NotFoundProblemResponse | UnprocessableEntityProblemResponse | InternalServerErrorProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse;

  @get @route("/account-snapshot-change-requests/{requestId}/documents/{documentType}")
  @doc("Stream a file from the original or proposal through the same owner/operations authorization as detail. source defaults to proposal. Arbitrary storage paths are not accepted. private,no-store response; missing file returns 404.")
  getAccountSnapshotChangeRequestDocument(@path requestId: Uuid, @path documentType: string, @query source?: "original" | "proposal" | null): OkAccountDocumentFileResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | NotFoundProblemResponse | UnprocessableEntityProblemResponse | InternalServerErrorProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse;

  @post @route("/account-snapshot-change-requests/{requestId}/approve")
  @doc("Requires account:snapshot-change-request:manage. Pending only; final requests return 422. Atomically save the current Account and copied documents as an approved snapshot and link reviewedSnapshotIdentifier to it. Historic references remain unchanged, and approved category history continues to prohibit changed-content normal saves (409 account_snapshot_change_requires_approval).")
  approveAccountSnapshotChangeRequest(@path requestId: Uuid): OkAccountSnapshotChangeRequest | UnauthorizedProblemResponse | ForbiddenProblemResponse | NotFoundProblemResponse | UnprocessableEntityProblemResponse | InternalServerErrorProblemResponse | CsrfTokenMismatchProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse;

  @post @route("/account-snapshot-change-requests/{requestId}/reject")
  @doc("Requires account:snapshot-change-request:manage. Pending only; terminal requests return 422. Records reviewer, time and reason, saves the current Account and copied documents as a rejected snapshot, and links reviewedSnapshotIdentifier to it. The current approved snapshot remains unchanged.")
  rejectAccountSnapshotChangeRequest(@path requestId: Uuid, @body body: RejectAccountCategoryChangeRequestBody): OkAccountSnapshotChangeRequest | UnauthorizedProblemResponse | ForbiddenProblemResponse | NotFoundProblemResponse | UnprocessableEntityProblemResponse | InternalServerErrorProblemResponse | CsrfTokenMismatchProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse;
}
