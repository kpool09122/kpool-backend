using TypeSpec.Http;

namespace KPool.WikiPrivateApi;

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

@route("/video-link")
interface VideoLinkOperations {
  @post
  @route("/save")
  @doc("Save video links for a wiki.")
  saveVideoLinks(
    @body body: SaveVideoLinksRequestBody,
  ): NoContentResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
