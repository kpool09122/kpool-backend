using TypeSpec.Http;

namespace KPool.Common;

@doc("Authenticated Laravel session cookie. Obtain it through the login flow and retain it for authenticated requests. Session API mutations also require CSRF protection: initialize it with GET /api/v1/identity/auth/csrf-token and send the URL-decoded XSRF-TOKEN cookie value in X-XSRF-TOKEN.")
model SessionCookieAuth is ApiKeyAuth<ApiKeyLocation.cookie, "k-pool_session">;

@doc("Short-lived internal API Bearer token, minted server-side from verified MCP authorization. Never send the MCP access token directly to this API. Authorization takes precedence over cookies with no fallback. Each request verifies the token, then checks the method/route scope allowlist and existing business permissions. Verified Bearer does not use a session or CSRF token. HTTP Bearer access stays disabled (403 oauth_api_disabled) until consent-scoped Account restrictions in #890 are complete. Account selection helpers need no extra scope; unregistered routes, including auto-create, consent APIs and authentication management, reject Bearer (403 oauth_route_not_allowed).")
model InternalApiBearerAuth is BearerAuth;
