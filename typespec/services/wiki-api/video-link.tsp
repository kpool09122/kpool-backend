using TypeSpec.Http;
using KPool.Common;

namespace KPool.WikiApi;

@doc("Input payload for a single video link.")
model VideoLinkInput {
  @doc("Video URL.")
  url: string;
  @doc("Usage category of the video.")
  videoUsage: string;
  @doc("Video title.")
  title?: string;
  @doc("Display order of the video link.")
  displayOrder: int32;
  @doc("Thumbnail image URL.")
  thumbnailUrl?: string;
  @doc("Publication timestamp of the video.")
  publishedAt?: string;
}

@doc("Request body for saving video links.")
model SaveVideoLinksRequestBody {
  @doc("Resource type that the video links belong to.")
  resourceType: string;
  @doc("Wiki identifier that the video links belong to.")
  wikiIdentifier: Uuid;
  @doc("Video links to save.")
  videoLinks: VideoLinkInput[];
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
  @route("/save")
  @doc("Save video links for a wiki.")
  saveVideoLinks(
    @body body: SaveVideoLinksRequestBody,
  ): KPool.Common.NoContentResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;
}
