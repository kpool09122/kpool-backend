using TypeSpec.Http;

namespace KPool.Common;

@doc("RFC 9457 Problem Details payload shared by JSON error responses.")
model ProblemDetails {
  @doc("URI reference identifying the problem type.")
  type?: string;

  @doc("HTTP status code.")
  status?: int32;

  @doc("Short error title.")
  title?: string;

  @doc("Human-readable error detail.")
  detail?: string;

  @doc("URI reference identifying the specific occurrence.")
  instance?: string;

  @doc("Machine-readable application error code. Authenticated endpoints use authentication_required for a missing or invalid login session, and recent_authentication_required when the current identity and login session lack valid recent authentication. Both return 401; clients must use this code rather than matching detail text. A non-active Principal returns 403 with principal_inactive, distinct from ordinary permission denial. Bulk Principal membership/status saves return 409 with last_active_manager if the final state leaves no reachable active permission manager, or principal_status_change_forbidden if a forbidden lifecycle transition is requested. Neither conflict commits any membership or status changes.")
  code?: string;
}

model UnauthorizedProblemResponse {
  @statusCode statusCode: 401;
  @body body: ProblemDetails;
}

model ForbiddenProblemResponse {
  @statusCode statusCode: 403;
  @body body: ProblemDetails;
}

model NotFoundProblemResponse {
  @statusCode statusCode: 404;
  @body body: ProblemDetails;
}

model ConflictProblemResponse {
  @statusCode statusCode: 409;
  @body body: ProblemDetails;
}

@doc("Atomic Principal membership/status save rejected. No submitted group or Principal change is committed.")
model PrincipalStatusConflictProblem extends ProblemDetails {
  @doc("last_active_manager: the final state loses the last reachable active permission manager. principal_status_change_forbidden: a forced-stop lifecycle transition was requested.")
  code: "last_active_manager" | "principal_status_change_forbidden";
}

model PrincipalStatusConflictProblemResponse {
  @statusCode statusCode: 409;
  @body body: PrincipalStatusConflictProblem;
}

model UnprocessableEntityProblemResponse {
  @statusCode statusCode: 422;
  @body body: ProblemDetails;
}

model TooManyRequestsProblemResponse {
  @statusCode statusCode: 429;
  @header("Retry-After") retryAfter: int32;
  @body body: ProblemDetails;
}

model ServiceUnavailableProblemResponse {
  @statusCode statusCode: 503;
  @body body: ProblemDetails;
}

model BadGatewayProblemResponse {
  @statusCode statusCode: 502;
  @body body: ProblemDetails;
}

model InternalServerErrorProblemResponse {
  @statusCode statusCode: 500;
  @body body: ProblemDetails;
}

model CsrfTokenMismatchProblemResponse {
  @statusCode statusCode: 419;
  @body body: ProblemDetails;
}
