namespace KPool.Common;

@doc("RFC 9457 Problem Details payload shared by JSON error responses.")
model ProblemDetails {
  @doc("HTTP status code.")
  status?: int32;

  @doc("Short error title.")
  title?: string;

  @doc("Human-readable error detail.")
  detail?: string;
}
