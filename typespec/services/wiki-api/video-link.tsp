using TypeSpec.Http;
using KPool.Common;

namespace KPool.WikiApi;

@doc("Input payload for a single video link.")
model CreateVideoLinkRequestBody {
  resourceType: "agency" | "group" | "song" | "talent";
  wikiIdentifier: Uuid;
  @doc("Video URL.")
  url: string;
  @doc("Usage category of the video.")
  videoUsage: "music_video" | "live" | "interview" | "behind_the_scenes" | "cover" | "collaboration" | "short" | "other";
  @doc("Video title.")
  @maxLength(255)
  title: string;
  @doc("Display order of the video link.")
  @minValue(0)
  displayOrder: int32;
  @minValue(0)
  durationSeconds: int32;
  @doc("Thumbnail image URL.")
  thumbnailUrl?: string | null;
  @doc("Publication timestamp of the video.")
  publishedAt?: string | null;
}

@doc("A public wiki video. Currently, only automatically collected YouTube videos are included.")
model Video {
  videoLinkIdentifier: Uuid;
  url: string;
  title: string;
  thumbnailUrl: string | null;
  publishedAt: string | null;
  displayOrder: int32;
  videoUsage: "youtube_auto_view_count" | "youtube_auto_like_count" | "youtube_auto_recent_popular";
}

model ListVideosResponse {
  @statusCode statusCode: 200;
  @body body: { videos: Video[]; };
}

interface PublicVideoLinkOperations {
  @get
  @route("/wiki/{language}/{resourceType}/{slug}/videos")
  @doc("Public, unauthenticated list of videos for the exact published wiki. Currently, only automatically collected videos are included. Ordered by displayOrder ascending, then videoLinkIdentifier ascending. A missing published wiki returns 404; zero videos returns videos: []. Collection targets are group/song/talent; agency returns an empty list. Viewing never calls YouTube or dispatches collection jobs.")
  listVideos(
    @path language: "ja" | "ko" | "en",
    @path resourceType: "agency" | "group" | "song" | "talent",
    @path slug: string,
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
}
