using TypeSpec.Http;
using KPool.Common;

namespace KPool.WikiPrivateApi;

@doc("Request body for uploading an image.")
model UploadImageRequestBody {
  @doc("Published image identifier to replace.")
  publishedImageIdentifier?: Uuid;
  @doc("Resource type that the image belongs to.")
  resourceType: string;
  @doc("Translation set identifier that the image belongs to.")
  translationSetIdentifier: Uuid;
  @doc("Image data encoded as base64.")
  base64EncodedImage: string;
  @doc("Display order of the image.")
  displayOrder: int32;
  @doc("Original source URL of the image.")
  sourceUrl: string;
  @doc("Original source name of the image.")
  sourceName: string;
  @doc("Alternative text for the image.")
  altText: string;
  @doc("Timestamp when the uploader agreed to the terms.")
  agreedToTermsAt: string;
  @doc("Whether the uploader confirmed there are no copyright or portrait rights issues.")
  rightsConfirmationAgreed: boolean;
}

@doc("Request body for creating an image deletion request.")
model RequestImageDeletionRequestBody {
  @doc("Name of the requester.")
  requesterName: string;
  @doc("Email address of the requester.")
  requesterEmail: string;
  @doc("Reason for requesting the image to be hidden.")
  reason: string;
}

@doc("Request body for reviewing an image deletion request.")
model ReviewImageDeletionRequestBody {
  @doc("Comment from the reviewer.")
  reviewerComment: string;
}

@doc("Uploaded image item returned by the uploaded image list endpoint.")
model UploadedImageListItem {
  @doc("Image identifier.")
  imageIdentifier: Uuid;
  @doc("Absolute image URL.")
  url: string;
  @doc("Resource type that the image belongs to.")
  resourceType: string;
  @doc("Translation set identifier that the image belongs to.")
  translationSetIdentifier: Uuid;
  @doc("Display order of the image.")
  displayOrder: int32;
  @doc("Original source URL of the image.")
  sourceUrl: string;
  @doc("Original source name of the image.")
  sourceName: string;
  @doc("Alternative text for the image.")
  altText: string;
  @doc("Whether the image is hidden.")
  isHidden: boolean;
  @doc("Timestamp when the image was uploaded.")
  uploadedAt: string | null;
}

@doc("Uploaded image item with pending image deletion request details.")
model ImageDeletionRequestListItem {
  @doc("Image identifier.")
  imageIdentifier: Uuid;
  @doc("Absolute image URL.")
  url: string;
  @doc("Resource type that the image belongs to.")
  resourceType: string;
  @doc("Translation set identifier that the image belongs to.")
  translationSetIdentifier: Uuid;
  @doc("Display order of the image.")
  displayOrder: int32;
  @doc("Original source URL of the image.")
  sourceUrl: string;
  @doc("Original source name of the image.")
  sourceName: string;
  @doc("Alternative text for the image.")
  altText: string;
  @doc("Whether the image is hidden.")
  isHidden: boolean;
  @doc("Timestamp when the image was uploaded.")
  uploadedAt: string | null;
  @doc("Name of the image deletion requester.")
  name: string;
  @doc("Email address of the image deletion requester.")
  email: string;
  @doc("Reason for the image deletion request.")
  reason: string;
}

@doc("Approval status assigned to a draft image.")
union DraftImageStatus {
  "approved",
  "pending",
  "rejected",
  "under_review",
}

@doc("Wiki display information linked to a draft image through its translation set.")
model DraftImageWikiDisplayInformation {
  @doc("Display names keyed by language.")
  names: Record<string>;
  @doc("Representative slug of the first linked wiki.")
  slug: string;
}

@doc("Draft image item returned by the draft image list endpoint.")
model DraftImageListItem {
  @doc("Draft image identifier.")
  imageIdentifier: Uuid;
  @doc("Published image identifier linked to this draft.")
  publishedImageIdentifier: Uuid | null;
  @doc("Absolute image URL.")
  url: string;
  @doc("Resource type that the image belongs to.")
  resourceType: string;
  @doc("Translation set identifier that the image belongs to.")
  translationSetIdentifier: Uuid;
  @doc("Display order of the image.")
  displayOrder: int32;
  @doc("Original source URL of the image.")
  sourceUrl: string;
  @doc("Original source name of the image.")
  sourceName: string;
  @doc("Alternative text for the image.")
  altText: string;
  @doc("Wiki display information linked to this image through its translation set.")
  wiki: DraftImageWikiDisplayInformation;
  @doc("Approval status of the draft image.")
  status: DraftImageStatus;
  @doc("Timestamp when the image was uploaded.")
  uploadedAt: string | null;
}

@doc("Response body for the uploaded image list endpoint.")
model ListUploadedImagesResponseBody {
  @doc("Uploaded images for the requested page.")
  images: UploadedImageListItem[];
  @doc("Current page number.")
  current_page: int32;
  @doc("Last page number.")
  last_page: int32;
  @doc("Total number of uploaded images.")
  total: int32;
  @doc("Number of images per page.")
  per_page: int32;
}

@doc("Response body for the pending image deletion request list endpoint.")
model ListImageDeletionRequestsResponseBody {
  @doc("Uploaded images that have pending image deletion requests for the requested page.")
  images: ImageDeletionRequestListItem[];
  @doc("Current page number.")
  current_page: int32;
  @doc("Last page number.")
  last_page: int32;
  @doc("Total number of pending image deletion requests.")
  total: int32;
  @doc("Number of images per page.")
  per_page: int32;
}

@doc("Response body for the draft image list endpoint.")
model ListDraftImagesResponseBody {
  @doc("Draft images for the requested page.")
  images: DraftImageListItem[];
  @doc("Current page number.")
  current_page: int32;
  @doc("Last page number.")
  last_page: int32;
  @doc("Total number of draft images.")
  total: int32;
  @doc("Number of images per page.")
  per_page: int32;
}

@doc("Response returned when uploaded image list retrieval succeeds.")
model ListUploadedImagesResponse {
  @statusCode statusCode: 200;
  @body body: ListUploadedImagesResponseBody;
}

@doc("Response returned when pending image deletion request list retrieval succeeds.")
model ListImageDeletionRequestsResponse {
  @statusCode statusCode: 200;
  @body body: ListImageDeletionRequestsResponseBody;
}

@doc("Response returned when draft image list retrieval succeeds.")
model ListDraftImagesResponse {
  @statusCode statusCode: 200;
  @body body: ListDraftImagesResponseBody;
}

@doc("Response returned when a draft image is created.")
model CreateDraftImageResponse {
  @statusCode statusCode: 201;
  @body body: ImageDraftSummary;
}

@doc("Response returned when an image operation succeeds.")
model OkImageResponse {
  @statusCode statusCode: 200;
  @body body: ImageSummary;
}

@doc("Response returned when an image deletion request is created.")
model CreateImageDeletionRequestResponse {
  @statusCode statusCode: 201;
  @body body: ImageDeletionRequestSummary;
}

@doc("Response returned when an image deletion request is reviewed.")
model ReviewImageDeletionRequestResponse {
  @statusCode statusCode: 200;
  @body body: ImageDeletionReviewSummary;
}

@route("/image")
interface ImageOperations {
  @post
  @route("/{imageId}/approve")
  @doc("Approve an image.")
  approveImage(
    @path imageId: Uuid,
  ): CreateDraftImageResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @delete
  @route("/{imageId}")
  @doc("Delete an image.")
  deleteImage(
    @path imageId: Uuid,
  ): KPool.Common.NoContentResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{imageId}/reject")
  @doc("Reject an image.")
  rejectImage(
    @path imageId: Uuid,
  ): OkImageResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{imageId}/unhide")
  @doc("Unhide an image.")
  unhideImage(
    @path imageId: Uuid,
  ): OkImageResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/upload")
  @doc("Upload a new image.")
  uploadImage(
    @body body: UploadImageRequestBody,
  ): CreateDraftImageResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{imageId}/request-deletion")
  @doc("Request that an image be hidden.")
  requestImageDeletion(
    @path imageId: Uuid,
    @body body: RequestImageDeletionRequestBody,
  ): CreateImageDeletionRequestResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{imageId}/approve-deletion")
  @doc("Approve an image deletion request.")
  approveImageDeletionRequest(
    @path imageId: Uuid,
    @body body: ReviewImageDeletionRequestBody,
  ): ReviewImageDeletionRequestResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{imageId}/reject-deletion")
  @doc("Reject an image deletion request.")
  rejectImageDeletionRequest(
    @path imageId: Uuid,
    @body body: ReviewImageDeletionRequestBody,
  ): ReviewImageDeletionRequestResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}

@route("/images")
interface ImageListOperations {
  @get
  @doc("List uploaded images.")
  listUploadedImages(
    @query translationSetIdentifier: Uuid,
    @query perPage?: int32,
  ): ListUploadedImagesResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}

@route("/draft-images")
interface DraftImageListOperations {
  @get
  @doc("List draft images.")
  listDraftImages(
    @query translationSetIdentifier?: Uuid | null,
    @query status: DraftImageStatus,
    @query perPage?: int32,
  ): ListDraftImagesResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
@route("/image-deletion-requests")
interface ImageDeletionRequestListOperations {
  @get
  @doc("List uploaded images with pending image deletion requests.")
  listImageDeletionRequests(
    @query perPage?: int32 | null,
  ): ListImageDeletionRequestsResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
