using TypeSpec.Http;
using KPool.Common;
namespace KPool.WikiApi;

model CreatePlaylistRequestBody {
  @doc("Name after trimming leading/trailing whitespace must contain 1 to 32 characters. The raw input may include additional surrounding whitespace. Duplicate names are allowed.")
  name: string;
}

model CreatedPlaylist {
  playlistIdentifier: Uuid;
  @minLength(1)
  @maxLength(32)
  name: string;
  @doc("A new playlist contains no videos.")
  videoCount: 0;
  createdAt: utcDateTime;
  updatedAt: utcDateTime;
}

model CreatePlaylistResponse {
  @statusCode statusCode: 201;
  @body body: CreatedPlaylist;
}

interface PlaylistOperations {
  @post
  @route("/my/playlists")
  @doc("Create an empty private playlist for the authenticated Wiki Principal; the owner cannot be supplied by the caller. Requires an active Account/Principal and Wiki collaborator CREATE_PLAYLIST permission. Accepts a name of 1-32 characters after trimming, including duplicate names. Returns 409 when the owner already has three playlists; deleting one allows creation again. Invalid names return 422. This endpoint never accepts video metadata from the caller. Playlist items store independent server-provided snapshots and retain them when source VideoLinks disappear.")
  createPlaylist(@body body: CreatePlaylistRequestBody): CreatePlaylistResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;
}
