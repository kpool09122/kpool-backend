using TypeSpec.Http;

namespace KPool.Common;

@doc("Authenticated Laravel session cookie. Obtain it through the login flow and retain it for authenticated requests. Session API mutations also require CSRF protection: initialize it with GET /api/identity/auth/csrf-token and send the URL-decoded XSRF-TOKEN cookie value in X-XSRF-TOKEN.")
model SessionCookieAuth is ApiKeyAuth<ApiKeyLocation.cookie, "k-pool_session">;
