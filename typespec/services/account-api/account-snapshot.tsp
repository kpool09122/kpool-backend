using TypeSpec.Http;
using KPool.Common;
namespace KPool.AccountApi;

@doc("A saved account snapshot. Saving does not approve service use. Identical account data and document bytes return the same identifier, status and savedAt without copying files or writing data.")
model AccountSnapshotSummary {
  snapshotIdentifier: Uuid;
  accountIdentifier: Uuid;
  status: "approved" | "rejected";
  savedAt: utcDateTime;
}
model AccountSnapshotProfile {
  email: string;
  type: "corporation" | "individual";
  name: string;
  status: string;
  phone: string;
  countryCode: string;
  administrativeAreaCode: string | null;
  postalCode: string;
  locality: string | null;
  addressLine1: string;
  addressLine2: string | null;
}
model AccountSnapshotDocument {
  documentType: string;
  uploadedAt: utcDateTime;
}
model AccountSnapshotDetail extends AccountSnapshotSummary {
  isCurrent: boolean;
  account: AccountSnapshotProfile;
  @doc("Documents ordered by documentType. Storage paths and content hashes are never returned. Read files through the authorized snapshot-specific endpoint.")
  documents: AccountSnapshotDocument[];
}
model OkAccountSnapshotSummary {
  @statusCode statusCode: 200;
  @body body: AccountSnapshotSummary;
}
model OkAccountSnapshotDetail {
  @statusCode statusCode: 200;
  @header("Cache-Control") cacheControl: "private, no-store";
  @body body: AccountSnapshotDetail;
}
interface AccountSnapshotOperations {
  @put @route("/accounts/{accountIdentifier}/snapshot")
  @doc("Save the current authenticated account profile and uploaded documents without review. Requires the same account and account:update. Complete contact/type and required documents are validated. Changed content creates a new immutable snapshot; previous snapshots and document files are retained. Profile updates, document uploads and snapshot saves are forbidden while a snapshot-change request is pending. Snapshot saves are also forbidden while a category-change request is pending; profile and document draft editing remains available. Otherwise identical-content saves succeed without writes even after approval. Changed contents are rejected with 409 (account_snapshot_change_requires_approval) if any category-change approval references a saved snapshot for the account. Does not change category-change requests.")
  saveAccountSnapshot(@path accountIdentifier: Uuid): OkAccountSnapshotSummary | ConflictProblemResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | NotFoundProblemResponse | UnprocessableEntityProblemResponse | InternalServerErrorProblemResponse | CsrfTokenMismatchProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse;

  @get @route("/accounts/{accountIdentifier}/snapshot")
  @doc("Read the current snapshot, defined as the latest approved snapshot ordered by savedAt descending then identifier descending for the account. Same-account readers require account:read; cross-account operations readers require account:category-change-request:manage on their own account. No current snapshot returns 404.")
  getCurrentAccountSnapshot(@path accountIdentifier: Uuid): OkAccountSnapshotDetail | UnauthorizedProblemResponse | ForbiddenProblemResponse | NotFoundProblemResponse | UnprocessableEntityProblemResponse | InternalServerErrorProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse;

  @get @route("/accounts/{accountIdentifier}/snapshots/{snapshotIdentifier}")
  @doc("Read a specific immutable snapshot with the same owner/operations authorization as the current-snapshot endpoint. Mismatched account/snapshot returns 404. Old application references remain valid after the current snapshot changes.")
  getAccountSnapshot(@path accountIdentifier: Uuid, @path snapshotIdentifier: Uuid): OkAccountSnapshotDetail | UnauthorizedProblemResponse | ForbiddenProblemResponse | NotFoundProblemResponse | UnprocessableEntityProblemResponse | InternalServerErrorProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse;

  @get @route("/accounts/{accountIdentifier}/snapshots/{snapshotIdentifier}/documents/{documentType}")
  @doc("Stream the copied document belonging to the specified account/snapshot after the same owner/operations authorization as snapshot reads. Missing document returns 404. Original-file edits/deletion do not change this file.")
  getAccountSnapshotDocument(@path accountIdentifier: Uuid, @path snapshotIdentifier: Uuid, @path documentType: string): OkAccountDocumentFileResponse | UnauthorizedProblemResponse | ForbiddenProblemResponse | NotFoundProblemResponse | UnprocessableEntityProblemResponse | InternalServerErrorProblemResponse | TooManyRequestsProblemResponse | ServiceUnavailableProblemResponse;
}
