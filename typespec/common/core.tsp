using TypeSpec.Http;

namespace KPool.Common;

@doc("Lifecycle of a context-specific Principal, independent of Account status. Only active authorizes protected operations. A forced_stop is set operationally, not through user permission management.")
enum PrincipalStatus {
  active,
  suspended,
  forced_stop,
}

@doc("A Principal status in a bulk save or its successful result. Omitted Principals are unchanged. Duplicate identifiers are rejected. An unchanged forced_stop is accepted, but a change from forced_stop or a new forced_stop is forbidden.")
model PrincipalStatusUpdate {
  principalIdentifier: Uuid;
  status: PrincipalStatus;
}

@format("uuid")
@doc("UUID identifier.")
scalar Uuid extends string;

@pattern("^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-7[0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$")
@doc("UUIDv7 identifier.")
scalar UuidV7 extends Uuid;

@doc("Timestamp in ISO 8601 format.")
scalar Timestamp extends string;

@doc("ISO 8601 date string.")
scalar DateString extends string;

@doc("Empty JSON object returned by endpoints with no payload.")
model EmptyJsonObject {}

@doc("Response with no content.")
model NoContentResponse {
  @statusCode statusCode: 204;
}
