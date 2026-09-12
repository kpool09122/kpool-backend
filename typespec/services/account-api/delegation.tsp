using TypeSpec.Http;
using KPool.Common;

namespace KPool.AccountApi;

@doc("Request body for requesting an account delegation.")
model RequestDelegationRequestBody {
  @doc("Account identifier of the affiliation counterpart.")
  targetAccountIdentifier: Uuid;
}

@doc("Account-level delegation request state.")
model AccountDelegationSummary {
  delegationIdentifier: Uuid;
  affiliationIdentifier: Uuid;
  delegateAccountIdentifier: Uuid;
  delegatorAccountIdentifier: Uuid;
  requestedByAccountIdentifier: Uuid;
  status: string;
  direction: string;
  requestedAt: Timestamp;
  approvedAt?: Timestamp | null;
  revokedAt?: Timestamp | null;
}

@doc("Response returned when a delegation is created.")
model CreateDelegationResponse {
  @statusCode statusCode: 201;
  @body body: AccountDelegationSummary;
}

@doc("Response returned when a delegation operation succeeds.")
model OkDelegationResponse {
  @statusCode statusCode: 200;
  @body body: AccountDelegationSummary;
}

@doc("Response returned when listing delegations succeeds.")
model ListDelegationsResponse {
  @statusCode statusCode: 200;
  @body body: ListDelegationsResponseBody;
}

@route("/delegations")
interface DelegationOperations {
  @get
  @doc("List delegations related to the authenticated account.")
  listDelegations(
    @query status?: string | null,
    @query viewerRole?: string | null,
    @query perPage?: int32 | null,
    @query page?: int32 | null,
  ): ListDelegationsResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @doc("Request a delegation.")
  requestDelegation(
    @body body: RequestDelegationRequestBody,
  ): CreateDelegationResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{delegationId}/approve")
  @doc("Approve a delegation.")
  approveDelegation(
    @path delegationId: Uuid,
  ): OkDelegationResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{delegationId}/reject")
  @doc("Reject a pending delegation.")
  rejectDelegation(
    @path delegationId: Uuid,
  ): KPool.Common.NoContentResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
