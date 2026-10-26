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

model PlaylistListItem {
  playlistIdentifier: Uuid;
  @minLength(1)
  @maxLength(32)
  name: string;
  @minValue(0)
  @maxValue(50)
  videoCount: int32;
  createdAt: utcDateTime;
  updatedAt: utcDateTime;
}

model ListMyPlaylistsResponse {
  @statusCode statusCode: 200;
  @body body: {
    @maxItems(3)
    playlists: PlaylistListItem[];
  };
}

model PlaylistItem {
  playlistItemIdentifier: Uuid;
  sourceVideoLinkIdentifier: Uuid | null;
  provider: "youtube";
  externalVideoId: string;
  @minValue(0)
  @maxValue(49)
  position: int32;
  url: url;
  title: string;
  thumbnailUrl: url | null;
  durationSeconds: int32 | null;
}

model PlaylistDetail extends PlaylistListItem {
  @maxItems(50)
  items: PlaylistItem[];
}

model GetMyPlaylistResponse {
  @statusCode statusCode: 200;
  @body body: PlaylistDetail;
}

interface PlaylistOperations {
  @delete
  @route("/my/playlists/{playlistId}")
  @doc("Delete the authenticated Wiki Principal's private playlist and its items, returning 204 without a body. Requires an active Account/Principal and Wiki collaborator DELETE_PLAYLIST permission. Other owners and missing identifiers return the same 404; malformed identifiers return 422. Original VideoLinks and likes are preserved. Deletion is reflected in list/detail queries and frees one of the owner's three creation slots.")
  deletePlaylist(@path playlistId: Uuid): KPool.Common.NoContentResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @get
  @route("/my/playlists/{playlistId}")
  @doc("Return the authenticated Wiki Principal's private playlist with all stored items, ordered by position then identifier, without pagination. Requires an active Account/Principal and Wiki collaborator GET_MY_PLAYLIST permission. Other owners and missing identifiers return the same 404; malformed identifiers return 422. Empty playlists return items: []. videoCount equals the stored item count. Snapshots remain available after source deletion (sourceVideoLinkIdentifier becomes null), Wiki unpublication or unlike. No YouTube requests or collection jobs are triggered; playback failures are handled by the player.")
  getMyPlaylist(@path playlistId: Uuid): GetMyPlaylistResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @get
  @route("/my/playlists")
  @doc("List only the authenticated Wiki Principal's private playlists in ascending creation time, then identifier order. Requires an active Account/Principal and Wiki collaborator LIST_MY_PLAYLISTS permission. Returns an empty array when none exist, with no pagination. videoCount counts stored PlaylistItems, including items whose source VideoLink was deleted, unliked or unpublished. The caller cannot specify the owner.")
  listMyPlaylists(): ListMyPlaylistsResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @post
  @route("/my/playlists")
  @doc("Create an empty private playlist for the authenticated Wiki Principal; the owner cannot be supplied by the caller. Requires an active Account/Principal and Wiki collaborator CREATE_PLAYLIST permission. Accepts a name of 1-32 characters after trimming, including duplicate names. Returns 409 when the owner already has three playlists; deleting one allows creation again. Invalid names return 422. This endpoint never accepts video metadata from the caller. Playlist items store independent server-provided snapshots and retain them when source VideoLinks disappear.")
  createPlaylist(@body body: CreatePlaylistRequestBody): CreatePlaylistResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;
}
