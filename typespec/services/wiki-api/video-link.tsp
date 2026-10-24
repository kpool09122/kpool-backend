using TypeSpec.Http;
using KPool.Common;

namespace KPool.WikiApi;

@doc("Input payload for a single video link.")
model CreateVideoLinkRequestBody {
  resourceType: "agency" | "group" | "song" | "talent";
  wikiIdentifier: Uuid;
  @doc("HTTPS YouTube video URL. Supports watch, youtu.be, shorts, live and embed URLs; other providers and non-video links are rejected. Stored as a canonical youtube.com/watch URL.")
  @maxLength(255)
  url: string;
  @doc("Usage category of the video.")
  videoUsage: "music_video" | "live" | "interview" | "behind_the_scenes" | "cover" | "collaboration" | "short" | "other";
  @doc("Video title.")
  @maxLength(255)
  title: string;
  @minValue(0)
  durationSeconds: int32;
  @doc("Thumbnail image URL.")
  thumbnailUrl?: string | null;
  @doc("Publication timestamp of the video.")
  publishedAt?: string | null;
}

@doc("A public wiki video. Includes manually created and automatically collected videos.")
model Video {
  videoLinkIdentifier: Uuid;
  url: string;
  title: string;
  thumbnailUrl: string | null;
  publishedAt: string | null;
  @doc("Video duration in seconds; null when unknown.")
  @minValue(0)
  durationSeconds: int32 | null;
  creatorPrincipalIdentifier: Uuid | null;
  @doc("The creator's current Identity user name. Null for automatically collected videos or when the creator's Principal or Identity no longer exists.")
  creatorIdentityName: string | null;
  videoUsage: "music_video" | "live" | "interview" | "behind_the_scenes" | "cover" | "collaboration" | "short" | "other" | "youtube_auto_view_count" | "youtube_auto_like_count" | "youtube_auto_recent_popular";
}

model ListVideosResponse {
  @statusCode statusCode: 200;
  @body body: { videos: Video[]; };
}

interface PublicVideoLinkOperations {
  @get
  @route("/wiki/{language}/{resourceType}/{slug}/videos")
  @doc("Public, unauthenticated list of videos for the exact published wiki. Includes manually created and automatically collected videos. creatorPrincipalIdentifier is the saved creator for manual videos and null for automatically collected videos. Agency includes manual videos only. Ordered by creation time descending, then videoLinkIdentifier ascending. A missing published wiki returns 404; zero videos returns videos: []. Viewing never calls YouTube or dispatches collection jobs.")
  listVideos(
    @path language: "ja" | "ko" | "en",
    @path resourceType: "agency" | "group" | "song" | "talent",
    @path slug: string,
    @doc("Inclusive minimum duration in seconds. Unknown durations are excluded when filtering.")
    @minValue(0)
    @query minDurationSeconds?: int32 | null,
    @doc("Inclusive maximum duration in seconds; omit for no upper limit. Must be at least minDurationSeconds.")
    @minValue(0)
    @query maxDurationSeconds?: int32 | null,
  ): ListVideosResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;
}

@route("/video-link")
interface VideoLinkOperations {
  @post
  @route("/create")
  @doc("Create one manual video without replacing existing videos. Requires an authenticated Wiki collaborator. durationSeconds is a non-negative integer obtained by the frontend player; zero is allowed. The creator principal identifier is determined on the server. Duplicate URLs within the wiki return 409.")
  createVideoLink(
    @body body: CreateVideoLinkRequestBody,
  ): KPool.Common.NoContentResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @delete
  @route("/{videoLinkId}")
  @doc("Delete only the specified video link. Requires an authenticated Wiki collaborator with an active Account/Principal and DELETE_VIDEO_LINK permission. Manual videos may be deleted only by their creator; all three automatically collected video categories may be deleted by any Wiki collaborator. Returns 204 without a body, 403 when forbidden, 404 when missing, and 422 for an invalid identifier. Deletion does not prevent future automatic re-collection.")
  deleteVideoLink(
    @path videoLinkId: Uuid,
  ): KPool.Common.NoContentResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

}
