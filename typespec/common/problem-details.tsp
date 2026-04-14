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

model UnprocessableEntityProblemResponse {
  @statusCode statusCode: 422;
  @body body: ProblemDetails;
}

model InternalServerErrorProblemResponse {
  @statusCode statusCode: 500;
  @body body: ProblemDetails;
}
