using TypeSpec.Http;
using KPool.Common;

namespace KPool.AccountApi;

@doc("Request body for creating an principal group.")
model CreatePrincipalGroupRequestBody {
  @doc("Owning account identifier.")
  accountIdentifier: Uuid;
  @doc("Principal group name.")
  name: string;
  @doc("Description of the permissions granted by this group.")
  @maxLength(256)
  description?: string | null;
}

@doc("Request body for mutating principal group membership.")
model MutatePrincipalGroupMemberRequestBody {
  @doc("Identity identifier to add or remove.")
  principalIdentifier: Uuid;
}

@doc("Principal group membership update item.")
model UpdatePrincipalGroupMembersItem {
  @doc("Principal group identifier whose members will be updated.")
  principalGroupIdentifier: Uuid;
  @doc("Principal identifiers that should belong to the principal group after the update.")
  principalIdentifiers: Uuid[];
}

@doc("Request body for updating multiple principal group memberships.")
model UpdatePrincipalGroupMembersRequestBody {
  @doc("Principal groups and their updated member sets.")
  principalGroups: UpdatePrincipalGroupMembersItem[];
  @doc("Statuses to save atomically with group memberships. Omission or an empty array makes no status changes; omitted Principals retain their status. All three values are accepted so unchanged forced_stop rows can be included.")
  principalStatuses?: PrincipalStatusUpdate[];
}

@doc("Response returned when an principal group is created.")
model CreatePrincipalGroupResponse {
  @statusCode statusCode: 201;
  @body body: CreatedPrincipalGroupSummary;
}

@doc("Response returned when an principal group operation succeeds.")
model OkPrincipalGroupResponse {
  @statusCode statusCode: 200;
  @body body: PrincipalGroupSummary;
}

@doc("Response returned when account members are listed.")
model ListMembersResponse {
  @statusCode statusCode: 200;
  @body body: ListMembersResponseBody;
}

@doc("Response returned when principal groups are listed.")
model ListPrincipalGroupsResponse {
  @statusCode statusCode: 200;
  @body body: ListPrincipalGroupsResponseBody;
}

@doc("Response returned when multiple principal group memberships are updated.")
model UpdatePrincipalGroupMembersResponse {
  @statusCode statusCode: 200;
  @body body: {
    principalGroups: UpdatedPrincipalGroupSummary[];
    principalStatuses: PrincipalStatusUpdate[];
  };
}

@doc("Updated Account group snapshot. Members are identifiers, not the list endpoint's detailed member projections.")
model UpdatedPrincipalGroupSummary {
  principalGroupIdentifier: Uuid;
  accountIdentifier: Uuid;
  name: string;
  description: string | null;
  roleIdentifiers: Uuid[];
  isDefault: boolean;
  members: Uuid[];
}

@route("/members")
interface MemberOperations {
  @get
  @doc("List account members.")
  listMembers(): ListMembersResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;
}

@route("/principal-groups")
interface PrincipalGroupOperations {
  @get
  @doc("List principal groups.")
  listPrincipalGroups(): ListPrincipalGroupsResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @doc("Create an principal group.")
  createPrincipalGroup(
    @body body: CreatePrincipalGroupRequestBody,
  ): CreatePrincipalGroupResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/{principalGroupId}/add-member")
  @doc("Add an identity to an principal group.")
  addPrincipalToPrincipalGroup(
    @path principalGroupId: Uuid,
    @body body: MutatePrincipalGroupMemberRequestBody,
  ): OkPrincipalGroupResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/{principalGroupId}/remove-member")
  @doc("Remove an identity from an principal group.")
  removePrincipalFromPrincipalGroup(
    @path principalGroupId: Uuid,
    @body body: MutatePrincipalGroupMemberRequestBody,
  ): OkPrincipalGroupResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @patch
  @route("/members")
  @doc("Atomically save specified group memberships and Principal statuses while preserving omitted groups and statuses. The final combined state must retain a reachable active permission manager; an active replacement and suspension of the old manager can be saved together. Duplicate status identifiers return 422; missing or out-of-scope Principals return 404; insufficient permission or protected Operator groups return 403. An unchanged forced_stop is accepted. A forbidden status change returns 409 code principal_status_change_forbidden; losing the last active manager returns 409 code last_active_manager. Both roll back the entire save. Success returns only the submitted groups and Principal statuses; clients refetch management lists separately. Status restrictions take effect on the next request even for logged-in cached sessions.")
  updatePrincipalGroupMembers(
    @body body: UpdatePrincipalGroupMembersRequestBody,
  ): UpdatePrincipalGroupMembersResponse | KPool.Common.PrincipalStatusConflictProblemResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @delete
  @route("/{principalGroupId}")
  @doc("Delete an principal group.")
  deletePrincipalGroup(
    @path principalGroupId: Uuid,
  ): KPool.Common.NoContentResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;
}
