using TypeSpec.Http;
using KPool.Common;

namespace KPool.WikiApi;

@doc("Request body for creating a principal.")
model CreatePrincipalRequestBody {
  @doc("Identity identifier to bind to the principal.")
  identityIdentifier: Uuid;
  @doc("Account identifier that owns the principal.")
  accountIdentifier: Uuid;
}

@doc("Request body for creating a principal group.")
model CreatePrincipalGroupRequestBody {
  @doc("Account identifier that owns the principal group.")
  accountIdentifier: Uuid;
  @doc("Principal group name.")
  name: string;
  @doc("Description of the permissions granted by this group.")
  @maxLength(256)
  description?: string | null;
}

@doc("Request body for adding or removing a principal group member.")
model MutatePrincipalGroupMemberRequestBody {
  @doc("Principal identifier to mutate.")
  principalIdentifier: Uuid;
}

@doc("Principal group membership update item.")
model UpdatePrincipalGroupMembersItem {
  @doc("Principal group identifier whose members will be updated.")
  principalGroupIdentifier: Uuid;
  @doc("Principal identifiers that should belong to the principal group after the update.")
  principalIdentifiers: Uuid[];
}

@doc("Request body for updating multiple Wiki principal group memberships.")
model UpdatePrincipalGroupMembersRequestBody {
  @doc("Principal groups and their updated member sets.")
  principalGroups: UpdatePrincipalGroupMembersItem[];
  @doc("Statuses to save atomically with group memberships. Omission or an empty array makes no status changes; omitted Principals retain their status. All three values are accepted so unchanged forced_stop rows can be included.")
  principalStatuses?: PrincipalStatusUpdate[];
}

@doc("Request body for attaching or detaching a role from a principal group.")
model AttachRoleToPrincipalGroupRequestBody {
  @doc("Role identifier to mutate.")
  roleIdentifier: Uuid;
}

@doc("Request body for creating a role.")
model CreateRoleRequestBody {
  @doc("Role name.")
  name: string;
  @doc("Policy identifiers attached to the role.")
  policies?: Uuid[];
  @doc("Account identifier. Omit or null for a system/global role.")
  accountId?: Uuid | null;
}

@doc("Request body for attaching or detaching a policy from a role.")
model AttachPolicyToRoleRequestBody {
  @doc("Policy identifier to mutate.")
  policyIdentifier: Uuid;
}

@doc("Request body for creating a policy.")
model CreatePolicyRequestBody {
  @doc("Policy name.")
  name: string;
  @doc("Statements that define the policy.")
  statements: PolicyStatement[];
  @doc("Account identifier. Omit or null for a system/global policy.")
  accountId?: Uuid | null;
}

@doc("Response returned when a principal is created.")
model CreatePrincipalResponse {
  @statusCode statusCode: 201;
  @body body: CreatedPrincipalSummary;
}

@doc("Response returned for the authenticated identity's principal.")
model GetCurrentPrincipalResponse {
  @statusCode statusCode: 200;
  @body body: PrincipalSummary;
}

@doc("Response returned when a principal group is created.")
model CreatePrincipalGroupResponse {
  @statusCode statusCode: 201;
  @body body: PrincipalGroupSummary;
}

@doc("Response returned when a principal group operation succeeds.")
model OkPrincipalGroupResponse {
  @statusCode statusCode: 200;
  @body body: PrincipalGroupSummary;
}

@doc("Response returned when Wiki principal groups are listed.")
model ListPrincipalGroupsResponse {
  @statusCode statusCode: 200;
  @body body: ListPrincipalGroupsResponseBody;
}

@doc("Response returned when multiple Wiki principal group memberships are updated.")
model UpdatePrincipalGroupMembersResponse {
  @statusCode statusCode: 200;
  @body body: {
    @doc("Updated groups only, matching the existing Wiki PATCH output.")
    principalGroups: PrincipalGroupSummary[];
    principalStatuses: PrincipalStatusUpdate[];
  };
}

@doc("Response returned when a role is created.")
model CreateRoleResponse {
  @statusCode statusCode: 201;
  @body body: RoleSummary;
}

@doc("Response returned when a policy is created.")
model CreatePolicyResponse {
  @statusCode statusCode: 201;
  @body body: PolicySummary;
}

@route("/")
interface PrincipalOperations {
  @get
  @route("/principal/me")
  @doc("Get the principal associated with the authenticated identity.")
  getCurrentPrincipal(): GetCurrentPrincipalResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/principal/create")
  @doc("Create a principal.")
  createPrincipal(
    @body body: CreatePrincipalRequestBody,
  ): CreatePrincipalResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @get
  @route("/principal-groups")
  @doc("List Wiki principal groups for management. Includes Collaborator and SeniorCollaborator groups with isMembershipEditable=false. Excludes every group containing the system Operator role, including mixed-role groups.")
  listPrincipalGroups(
    @query accountIdentifier: Uuid,
  ): ListPrincipalGroupsResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/principal-group/create")
  @doc("Create a principal group.")
  createPrincipalGroup(
    @body body: CreatePrincipalGroupRequestBody,
  ): CreatePrincipalGroupResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/principal-group/{principalGroupId}/add-member")
  @doc("Add a principal to a principal group.")
  addPrincipalToPrincipalGroup(
    @path principalGroupId: Uuid,
    @body body: MutatePrincipalGroupMemberRequestBody,
  ): OkPrincipalGroupResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/principal-group/{principalGroupId}/remove-member")
  @doc("Remove a principal from a principal group.")
  removePrincipalFromPrincipalGroup(
    @path principalGroupId: Uuid,
    @body body: MutatePrincipalGroupMemberRequestBody,
  ): OkPrincipalGroupResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @patch
  @route("/principal-groups/members")
  @doc("Atomically save specified group memberships and Principal statuses while preserving omitted groups and statuses. The final combined state must retain a reachable active permission manager; an active replacement and suspension of the old manager can be saved together. Duplicate status identifiers return 422; missing or out-of-scope Principals return 404; insufficient permission or protected Operator, Collaborator, or SeniorCollaborator groups return 403. An unchanged forced_stop is accepted. A forbidden status change returns 409 code principal_status_change_forbidden; losing the last active manager returns 409 code last_active_manager. Both roll back the entire save. Success returns only the submitted groups and Principal statuses; clients refetch management lists separately. Status restrictions take effect on the next request even for logged-in cached sessions.")
  updatePrincipalGroupMembers(
    @body body: UpdatePrincipalGroupMembersRequestBody,
  ): UpdatePrincipalGroupMembersResponse | KPool.Common.PrincipalStatusConflictProblemResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @delete
  @route("/principal-group/{principalGroupId}")
  @doc("Delete a principal group.")
  deletePrincipalGroup(
    @path principalGroupId: Uuid,
  ): KPool.Common.NoContentResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/principal-group/{principalGroupId}/attach-role")
  @doc("Attach a role to a principal group.")
  attachRoleToPrincipalGroup(
    @path principalGroupId: Uuid,
    @body body: AttachRoleToPrincipalGroupRequestBody,
  ): KPool.Common.NoContentResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/principal-group/{principalGroupId}/detach-role")
  @doc("Detach a role from a principal group.")
  detachRoleFromPrincipalGroup(
    @path principalGroupId: Uuid,
    @body body: AttachRoleToPrincipalGroupRequestBody,
  ): KPool.Common.NoContentResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/role/create")
  @doc("Create a role.")
  createRole(
    @body body: CreateRoleRequestBody,
  ): CreateRoleResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @delete
  @route("/role/{roleId}")
  @doc("Delete a role.")
  deleteRole(
    @path roleId: Uuid,
  ): KPool.Common.NoContentResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/role/{roleId}/attach-policy")
  @doc("Attach a policy to a role.")
  attachPolicyToRole(
    @path roleId: Uuid,
    @body body: AttachPolicyToRoleRequestBody,
  ): KPool.Common.NoContentResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/role/{roleId}/detach-policy")
  @doc("Detach a policy from a role.")
  detachPolicyFromRole(
    @path roleId: Uuid,
    @body body: AttachPolicyToRoleRequestBody,
  ): KPool.Common.NoContentResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/policy/create")
  @doc("Create a policy.")
  createPolicy(
    @body body: CreatePolicyRequestBody,
  ): CreatePolicyResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @delete
  @route("/policy/{policyId}")
  @doc("Delete a policy.")
  deletePolicy(
    @path policyId: Uuid,
  ): KPool.Common.NoContentResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;
}
