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
  @doc("Number of ordinary k-pool likes for this VideoLink, excluding official-owner likes and YouTube likes. Only ordinary likes participate in popularity ordering. No caller-specific state is included.")
  @minValue(0)
  likeCount: int32;
  @doc("Whether any saved like was made by the non-delegated owner of this exact officially certified talent wiki at the time of liking. Independent of likeCount; no owner or liker identifiers are exposed. Classification is retained until unlike, even if certification or ownership later changes.")
  hasOfficialOwnerLike: boolean;
  @doc("Whether the current non-delegated official owner has recorded completed playback of this exact video. Independent of likes; no viewer identifiers are exposed.")
  hasOfficialOwnerWatch?: boolean;
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
    @doc("Only videos with at least one request sent less than 168 hours ago. Requests exactly 168 hours old are excluded; each video appears only once.")
    @query recentWatchRequestsOnly?: boolean | null,
    @doc("Order by request count in the last 168 hours descending, then ordinary k-pool like count (excluding official-owner likes) descending, then videoLinkIdentifier ascending. Omitted/false preserves the default creation-time order. Can combine with duration bounds and recentWatchRequestsOnly.")
    @query sortByWatchRequests?: boolean | null,
  ): ListVideosResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;
}


model VideoWatchRequestStatus {
  videoLinkIdentifier: Uuid;
  @doc("Whether this caller has a request for this video less than 168 hours old; never another sender's personal state.")
  sent: boolean;
  canSend: boolean;
  reason: "target_not_eligible" | "duration_not_eligible" | "like_required" | "resend_not_available" | "weekly_limit_reached" | "already_watched" | null;
  lastSentAt: string | null;
  @doc("Earliest time allowed by same-video cooldown and wiki-wide weekly quota; null when neither restriction applies. Other eligibility requirements still apply.")
  nextSendAt: string | null;
  @minValue(0)
  remainingRequests: int32;
  @doc("Expiry of this caller's active request, or null. Does not indicate overall UNLIKE permission.")
  unlikeRestrictedUntil: string | null;
  @doc("Expiry of the latest active request from any sender, or null. Does not indicate ownership or DELETE permission.")
  deleteRestrictedUntil: string | null;
}

interface VideoLinkWatchOperations {
  @post
  @route("/video-link/{videoLinkId}/watched")
  @doc("Record completed playback of a video on its exact officially certified talent wiki. Only its active, non-delegated owner Account may record completion; editors, other owners and delegated Principals receive 403. Video, owner, Principal and first completion time are resolved by the server. Idempotent per video and owner Account, returning 204 for repeated completion; does not create a like or request. A current-owner completion permanently prevents new watch requests for this video with 409 video_already_watched, independent of the rolling request window. Manual skips and playback errors must not invoke this endpoint.")
  recordVideoLinkWatch(@path videoLinkId: Uuid): KPool.Common.NoContentResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;
}

interface VideoWatchRequestOperations {
  @post
  @route("/video-link/{videoLinkId}/watch-requests")
  @doc("A video already watched by its current official owner returns 409 video_already_watched. Send a persistent watch request as an active authenticated Wiki collaborator with SEND_VIDEO_WATCH_REQUEST permission. The video must belong directly to an officially certified talent wiki and be user-created, liked by the caller, and confirmed to be at most 30 seconds (30 inclusive). The send policy requires a talent video on an officially certified talent wiki and user-created usage; policy rejection returns 403. Unknown/long stored durations are rejected with 409. Sender and target wiki are determined on the server. Eligibility uses durationSeconds stored in the database for both YouTube and TikTok; a missing stored duration is ineligible. No external duration lookup is performed. Sender, exact target wiki/video and server timestamp are stored permanently. Maximum three requests per caller/talent translation set across all languages in the last 168 hours; same-video resend, own unlike and any deletion are blocked for 168 hours, expiring exactly at that boundary. Success 204. A missing target wiki returns 409 video_watch_request_target_not_eligible. Other eligibility/limit failures return 409 video_watch_request_duration_not_eligible, video_watch_request_like_required, video_watch_request_resend_not_available or video_watch_request_weekly_limit_reached. Other callers' histories are never returned.")
  sendVideoWatchRequest(@path videoLinkId: Uuid): KPool.Common.NoContentResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @get
  @route("/my/video-watch-requests")
  @doc("Return personal watch-request state for 1 to 100 explicitly supplied video IDs, ordered by videoLinkIdentifier with duplicate IDs collapsed. All IDs must exist (404 otherwise). Requires LIST_MY_VIDEO_WATCH_REQUESTS permission for every target resource type. Send permission is evaluated per video with its real resource attributes; a rejected send policy returns canSend=false with target_not_eligible, without rejecting the entire batch. No sender identifier input. Uses the same stored database duration and rolling 168-hour eligibility boundary as sending; missing stored duration yields canSend=false with duration_not_eligible. lastSentAt includes historical sends; sent only means an active send. Restrictions are independent: another sender can keep deletion restricted after this caller's unlike restriction expires. remainingRequests is scoped to each video's exact wiki and caller. This is a snapshot; send always revalidates. This endpoint does not send requests. Missing/empty IDs, invalid UUIDs or more than 100 IDs return 422.")
  listMyVideoWatchRequests(
    @minItems(1)
    @maxItems(100)
    @query(#{name: "videoLinkIds[]", explode: true}) videoLinkIds: Uuid[],
  ): { @statusCode statusCode: 200; @body body: { videoWatchRequests: VideoWatchRequestStatus[]; }; } | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;
}

interface VideoLinkLikeOperations {
  @post
  @route("/video-link/{videoLinkId}/like")
  @doc("Like a manual or automatically collected VideoLink as the authenticated Wiki Principal. Requires LIKE_VIDEO_LINK permission for its resource type. The server classifies a like as official-owner only when the exact target wiki and video are talent resources, the wiki is official, and the non-delegated Principal belongs to its owner Account. Editing rights, roles, other wiki ownership and delegated owner Accounts do not confer this classification. No caller-supplied owner, sender or kind is accepted. Exactly one record per video/Principal, never counted twice. Returns 409 if the Principal has already liked the VideoLink, regardless of kind; existing classification is not changed by retry.")
  likeVideoLink(@path videoLinkId: Uuid): KPool.Common.NoContentResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @delete
  @route("/video-link/{videoLinkId}/like")
  @doc("Remove only the authenticated Wiki Principal's like, including official-owner likes; another Principal's like is never removed. Requires UNLIKE_VIDEO_LINK permission. Repeated removal is idempotent (204); a missing VideoLink returns 404. Returns 409 (video_watch_request_active) while the authenticated sender has a request less than 168 hours old. At exactly 168 hours this restriction expires.")
  unlikeVideoLink(@path videoLinkId: Uuid): KPool.Common.NoContentResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

  @get
  @route("/my/video-link-likes")
  @doc("Return likes of the authenticated active Wiki Principal. With videoLinkIds[], return all matching liked IDs in identifier order without pagination, even for non-public or missing wikis; videos includes only existing videos on published wikis and pagination is null. Omitted IDs list existing videos on published wikis across languages and resource types, sorted by likedAt descending then videoLinkIdentifier ascending, with matching IDs in the same order and pagination metadata. Each VideoLink is counted independently even when URLs match. Empty lists return 200. Explicit empty IDs, invalid UUIDs, more than 100 IDs or invalid pagination return 422. Does not accept a principal ID or like kind. Both ordinary and official-owner likes appear in likedVideoLinkIdentifiers and videos; isOfficialOwnerLike on each video identifies the saved kind, not current ownership. The usual authenticated active Account/Principal checks and permissions apply to both kinds.")
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
  @doc("Whether this authenticated Principal's saved like was classified as an official-owner like by the server when created. False for ordinary likes. Not a client input.")
  isOfficialOwnerLike: boolean;
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
  @doc("Delete only the specified video link. Requires an authenticated Wiki collaborator with an active Account/Principal and DELETE_VIDEO_LINK permission. Manual videos may be deleted only by their creator; all three automatically collected video categories may be deleted by any Wiki collaborator. Returns 204 without a body, 403 when forbidden, 404 when missing, and 422 for an invalid identifier. Deletion does not prevent future automatic re-collection. Any request less than 168 hours old prevents deletion with 409 (video_watch_request_active), regardless of sender. The latest sender determines expiry; at exactly 168 hours that request stops blocking deletion.")
  deleteVideoLink(
    @path videoLinkId: Uuid,
  ): KPool.Common.NoContentResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse | KPool.Common.CsrfTokenMismatchProblemResponse | KPool.Common.TooManyRequestsProblemResponse | KPool.Common.ServiceUnavailableProblemResponse;

}
