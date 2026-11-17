using TypeSpec.Http;

@service(#{ title: "k-pool MCP" })
@server("/", "Stateless MCP transport, metadata and OAuth token exchange. Disabled until MCP_SERVER_ENABLED=true and deployment verification is complete.")
namespace KPool.Mcp;

model ResourceMetadata {
  resource: url;
  authorization_servers: url[];
  scopes_supported: string[];
  bearer_methods_supported: "header"[];
}
model AuthorizationServerMetadata {
  issuer: url;
  @doc("Configured browser consent entry URL (frontend #631/#632). The client sends its original OAuth parameters, including opaque state and PKCE, directly to this URL. Login returnTo and explicit consent are handled by the frontend; this is not the authenticated request-creation API.")
  authorization_endpoint: url;
  token_endpoint: url;
  response_types_supported: "code"[];
  grant_types_supported: ("authorization_code" | "refresh_token")[];
  code_challenge_methods_supported: "S256"[];
  token_endpoint_auth_methods_supported: ("none" | "client_secret_post" | "client_secret_basic")[];
  scopes_supported: string[];
}
model ProtocolError { error: string; }
model HttpError {
  @statusCode statusCode: 400 | 401 | 403 | 404 | 406 | 413 | 415 | 422 | 429 | 500 | 502 | 503;
  @header("WWW-Authenticate") challenge?: string;
  @header("Retry-After") retryAfter?: string;
  @body body: ProtocolError | KPool.Common.ProblemDetails;
}
model RpcRequest {
  jsonrpc: "2.0";
  id?: string | integer;
  method: "initialize" | "ping" | "tools/list" | "tools/call" | "notifications/initialized" | string;
  params?: Record<unknown>;
}
model AccountReference { accountIdentifier: string; name: string; }
model AccountContext {
  accountIdentifier: string | null;
  delegationIdentifier: string | null;
  account: AccountReference | null;
  originalAccount: AccountReference | null;
  switchableAccounts: {
    delegationIdentifier: string | null;
    accountIdentifier: string;
    account: AccountReference;
    isCurrent: boolean;
  }[];
}
model ToolError {
  status: int32;
  code: "forbidden" | "not_found" | "conflict" | "validation_failed" | "rate_limited" | "api_unavailable" | "invalid_api_response" | "invalid_api_configuration";
  retryAfter: int32 | null;
}
model ToolResult {
  content: { type: "text"; text: string; }[];
  structuredContent: AccountContext | { switched: true; } | ToolError;
  isError: boolean;
}
model ToolDefinition {
  name: "get_account_context" | "switch_account";
  description: string;
  inputSchema: Record<unknown>;
  @doc("OAuth-required Account selection helpers have no additional business scope, matching existing HTTP API. Subsequent business tools must require the granted token scopes as well as current Account permissions.")
  securitySchemes: { type: "oauth2"; scopes: string[]; }[];
  _meta: Record<unknown>;
  annotations: Record<boolean>;
}
model RpcResponse {
  jsonrpc: "2.0";
  id: string | integer | null;
  result?: ToolResult | { tools: ToolDefinition[]; } | Record<unknown>;
  error?: { code: int32; message: string; };
}
model RpcHttpResponse { @statusCode statusCode: 200 | 400; @body body: RpcResponse; }
model NotificationAccepted { @statusCode statusCode: 202; }
model MethodNotAllowed { @statusCode statusCode: 405; @header("Allow") allow: "POST"; }

@route("/.well-known/oauth-protected-resource/mcp")
@get op getProtectedResourceMetadata(): ResourceMetadata | HttpError;
@route("/.well-known/oauth-protected-resource")
@get op getRootProtectedResourceMetadata(): ResourceMetadata | HttpError;
@route("/.well-known/oauth-authorization-server")
@get op getAuthorizationServerMetadata(): AuthorizationServerMetadata | HttpError;

@route("/mcp")
@post
@useAuth(BearerAuth)
@doc("Streamable HTTP JSON responses, with no server session or unsolicited SSE. Every request requires an MCP-audience Bearer; Cookie and internal-API tokens are not accepted. tools/call arguments are strictly validated: get_account_context takes {}, switch_account requires delegationIdentifier (UUID or null for original Account). Tool notifications never execute side effects. 401/insufficient_scope 403 carry WWW-Authenticate; other API 403/404/409/422/429 return isError=true with sanitized structuredContent.status/code/retryAfter. No automatic retries, redirects or user-supplied API URLs.")
op sendMessage(
  @header("Accept") accept: string,
  @header("MCP-Protocol-Version") protocolVersion?: string,
  @header("Origin") origin?: string,
  @body body: RpcRequest,
): RpcHttpResponse | NotificationAccepted | HttpError;
@route("/mcp") @get @useAuth(BearerAuth) op listenForMessages(): MethodNotAllowed | HttpError;
@route("/mcp") @delete @useAuth(BearerAuth) op terminateSession(): MethodNotAllowed | HttpError;

model TokenRequest {
  grant_type: "authorization_code" | "refresh_token";
  client_id?: string;
  client_secret?: string;
  code?: string;
  code_verifier?: string;
  redirect_uri?: url;
  @doc("Required for authorization_code. If sent on refresh, must equal the original MCP resource exactly.")
  resource?: url;
  refresh_token?: string;
  @doc("Refresh may only narrow the previously granted scopes. Forbidden on authorization_code exchange.")
  scope?: string;
}
model Tokens {
  token_type: "Bearer";
  expires_in: int32;
  access_token: string;
  refresh_token: string;
}
@route("/oauth/token") @post
@doc("Pre-registered clients only. PKCE S256, resource, exact redirect URI and revocation are validated by McpAuthorizationService. Refresh issuance/revocation is one DB transaction; old internal-API authorization caches are invalidated after commit. Tokens are returned only to the OAuth client, never through tools.")
op exchangeToken(
  @header("Content-Type") contentType: "application/x-www-form-urlencoded",
  @header("Authorization") clientAuthentication?: string,
  @body body: TokenRequest,
): Tokens | HttpError;
