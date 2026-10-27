using TypeSpec.Http;
using KPool.Common;

namespace KPool.WikiApi;

@doc("Input payload for a single video link.")
model CreateVideoLinkRequestBody {
  resourceType: "agency" | "group" | "song" | "talent";
  wikiIdentifier: Uuid;
  @doc("HTTPS YouTube or TikTok video URL. YouTube supports watch, youtu.be, shorts, live and embed URLs. TikTok supports www.tiktok.com/@user/video/string-id and vm.tiktok.com / vt.tiktok.com share links. Canonicalized by the server; profiles, photos and other hosts return 422.")
  @maxLength(255)
  url: string;
  @doc("Usage category of the video.")
  videoUsage: "music_video" | "live" | "interview" | "behind_the_scenes" | "cover" | "collaboration" | "short" | "other";
  @doc("Video title.")
  @maxLength(255)
  title: string;
  @minValue(0)
  @doc("Required for YouTube; non-negative player duration including zero. Optional or null for TikTok and ignored there: the server resolves duration from the selected public video.")
  durationSeconds?: int32 | null;
  @doc("Optional YouTube thumbnail URL. Ignored for TikTok: the server stores the selected video cover and returns its own public image URL.")
  thumbnailUrl?: string | null;
  @doc("Publication timestamp of the video.")
  publishedAt?: string | null;
}

@doc("A public wiki video. Includes manually created and automatically collected videos.")
model Video {
  provider: "youtube" | "tiktok";
  @doc("Provider-specific video ID, always a string. YouTube: 11 characters; TikTok: decimal digits.")
  externalVideoId: string;
  videoLinkIdentifier: Uuid;
  @doc("Number of k-pool likes for this VideoLink, not YouTube likes. No personal state is included.")
  @minValue(0)
  likeCount: int32;
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
  @doc("Public, unauthenticated list of videos for the exact published wiki. Includes manually created and automatically collected videos. creatorPrincipalIdentifier is the saved creator for manual videos and null for automatically collected videos. Agency includes manual videos only. Ordered by creation time descending, then videoLinkIdentifier ascending. A missing published wiki returns 404; zero videos returns videos: []. Viewing never calls YouTube or TikTok or dispatches collection jobs.")
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


interface VideoLinkLikeOperations {
  @post
  @route("/video-link/{videoLinkId}/like")
  @doc("Like a manual or automatically collected VideoLink as the authenticated Wiki Principal. Requires LIKE_VIDEO_LINK permission for its resource type. Returns 409 if the Principal has already liked the VideoLink.")
  likeVideoLink(@path videoLinkId: Uuid): KPool.Common.NoContentResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @delete
  @route("/video-link/{videoLinkId}/like")
  @doc("Remove only the authenticated Wiki Principal's like. Requires UNLIKE_VIDEO_LINK permission. Repeated removal is idempotent (204); a missing VideoLink returns 404.")
  unlikeVideoLink(@path videoLinkId: Uuid): KPool.Common.NoContentResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @get
  @route("/my/video-link-likes")
  @doc("Return likes of the authenticated active Wiki Principal. With videoLinkIds[], return all matching liked IDs in identifier order without pagination, even for non-public or missing wikis; videos includes only existing videos on published wikis and pagination is null. Omitted IDs list existing videos on published wikis across languages and resource types, sorted by likedAt descending then videoLinkIdentifier ascending, with matching IDs in the same order and pagination metadata. Each VideoLink is counted independently even when URLs match. Empty lists return 200. Explicit empty IDs, invalid UUIDs, more than 100 IDs or invalid pagination return 422. Does not accept a principal ID.")
  listMyVideoLinkLikes(
    @minItems(1)
    @maxItems(100)
    @query(#{name: "videoLinkIds[]", explode: true}) videoLinkIds?: Uuid[],
    @minValue(1)
    @query page?: int32 = 1,
    @minValue(1)
    @maxValue(100)
    @query perPage?: int32 = 20,
  ): ListMyVideoLinkLikesResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;
}

model LikedVideoLink {
  provider: "youtube" | "tiktok";
  @doc("Provider-specific video ID, always a string. YouTube: 11 characters; TikTok: decimal digits.")
  externalVideoId: string;
  videoLinkIdentifier: Uuid;
  url: string;
  title: string;
  thumbnailUrl: string | null;
  @minValue(0)
  durationSeconds: int32 | null;
  language: "ja" | "ko" | "en";
  resourceType: "agency" | "group" | "song" | "talent";
  slug: string;
  likedAt: string;
  @doc("The authenticated Principal's saved playlists containing the same YouTube or TikTok video, matched by provider and external video ID across languages and original VideoLinks. Ordered by playlist creation time then identifier. Empty when not in a playlist. Other Principals' playlists are never included.")
  playlists: LikedVideoPlaylist[];
}

model LikedVideoPlaylist {
  playlistIdentifier: Uuid;
  name: string;
}

model VideoLinkLikesPagination {
  currentPage: int32;
  perPage: int32;
  total: int32;
}

model ListMyVideoLinkLikesResponse {
  @statusCode statusCode: 200;
  @body body: {
    likedVideoLinkIdentifiers: Uuid[];
    videos: LikedVideoLink[];
    pagination: VideoLinkLikesPagination | null;
  };
}

@route("/video-link")
interface VideoLinkOperations {
  @post
  @route("/create")
  @doc("Create one manual video without replacing existing videos. Requires an authenticated Wiki collaborator. YouTube requires a non-negative frontend-player duration; zero is allowed. TikTok ignores client thumbnail/duration and resolves the selected public video before storing its cover. The creator is determined on the server. Duplicate provider/video IDs within the wiki return 409, including URL variants. Invalid URL or resolved non-video share links return 422. Unavailable, restricted, changed or invalid public metadata/image and storage failures return 502 (video_metadata_unavailable); no video is saved. Successful response is 204 without a body.")
  createVideoLink(
    @body body: CreateVideoLinkRequestBody,
  ): KPool.Common.BadGatewayProblemResponse | KPool.Common.NoContentResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @delete
  @route("/{videoLinkId}")
  @doc("Delete only the specified video link. Requires an authenticated Wiki collaborator with an active Account/Principal and DELETE_VIDEO_LINK permission. Manual videos may be deleted only by their creator; all three automatically collected video categories may be deleted by any Wiki collaborator. Returns 204 without a body, 403 when forbidden, 404 when missing, and 422 for an invalid identifier. Deletion does not prevent future automatic re-collection.")
  deleteVideoLink(
    @path videoLinkId: Uuid,
  ): KPool.Common.NoContentResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

}
