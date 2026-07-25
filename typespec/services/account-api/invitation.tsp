using TypeSpec.Http;
using KPool.Common;

namespace KPool.AccountApi;

@doc("Request body for inviting account members.")
model InviteMemberRequestBody {
  @doc("Account identifier to invite members to.")
  accountIdentifier: Uuid;
  @doc("Identity identifier sending the member invitations.")
  inviterPrincipalIdentifier: Uuid;
  @doc("Invitee email addresses.")
  emails: string[];
}

@doc("Response returned when members are invited.")
model InviteMemberResponse {
  @statusCode statusCode: 201;
  @body body: InvitationSummary[];
}

@route("/invitations")
interface InvitationOperations {
  @post
  @doc("Invite members for one or more email addresses.")
  inviteMember(
    @body body: InviteMemberRequestBody,
  ): InviteMemberResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
