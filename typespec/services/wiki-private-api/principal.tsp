using TypeSpec.Http;

namespace KPool.WikiPrivateApi;

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
}

@doc("Request body for adding or removing a principal group member.")
model MutatePrincipalGroupMemberRequestBody {
  @doc("Principal identifier to mutate.")
  principalIdentifier: Uuid;
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
  @doc("Whether this is a system role.")
  isSystemRole: boolean;
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
  @doc("Whether this is a system policy.")
  isSystemPolicy: boolean;
}

@doc("Response returned when a principal is created.")
model CreatePrincipalResponse {
  @statusCode statusCode: 201;
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
  @post
  @route("/principal/create")
  @doc("Create a principal.")
  createPrincipal(
    @body body: CreatePrincipalRequestBody,
  ): CreatePrincipalResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/principal-group/create")
  @doc("Create a principal group.")
  createPrincipalGroup(
    @body body: CreatePrincipalGroupRequestBody,
  ): CreatePrincipalGroupResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/principal-group/{principalGroupId}/add-member")
  @doc("Add a principal to a principal group.")
  addPrincipalToPrincipalGroup(
    @path principalGroupId: Uuid,
    @body body: MutatePrincipalGroupMemberRequestBody,
  ): OkPrincipalGroupResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/principal-group/{principalGroupId}/remove-member")
  @doc("Remove a principal from a principal group.")
  removePrincipalFromPrincipalGroup(
    @path principalGroupId: Uuid,
    @body body: MutatePrincipalGroupMemberRequestBody,
  ): OkPrincipalGroupResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @delete
  @route("/principal-group/{principalGroupId}")
  @doc("Delete a principal group.")
  deletePrincipalGroup(
    @path principalGroupId: Uuid,
  ): NoContentResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/principal-group/{principalGroupId}/attach-role")
  @doc("Attach a role to a principal group.")
  attachRoleToPrincipalGroup(
    @path principalGroupId: Uuid,
    @body body: AttachRoleToPrincipalGroupRequestBody,
  ): NoContentResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/principal-group/{principalGroupId}/detach-role")
  @doc("Detach a role from a principal group.")
  detachRoleFromPrincipalGroup(
    @path principalGroupId: Uuid,
    @body body: AttachRoleToPrincipalGroupRequestBody,
  ): NoContentResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/role/create")
  @doc("Create a role.")
  createRole(
    @body body: CreateRoleRequestBody,
  ): CreateRoleResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @delete
  @route("/role/{roleId}")
  @doc("Delete a role.")
  deleteRole(
    @path roleId: Uuid,
  ): NoContentResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/role/{roleId}/attach-policy")
  @doc("Attach a policy to a role.")
  attachPolicyToRole(
    @path roleId: Uuid,
    @body body: AttachPolicyToRoleRequestBody,
  ): NoContentResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/role/{roleId}/detach-policy")
  @doc("Detach a policy from a role.")
  detachPolicyFromRole(
    @path roleId: Uuid,
    @body body: AttachPolicyToRoleRequestBody,
  ): NoContentResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/policy/create")
  @doc("Create a policy.")
  createPolicy(
    @body body: CreatePolicyRequestBody,
  ): CreatePolicyResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @delete
  @route("/policy/{policyId}")
  @doc("Delete a policy.")
  deletePolicy(
    @path policyId: Uuid,
  ): NoContentResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
