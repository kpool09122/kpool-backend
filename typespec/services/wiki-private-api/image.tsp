using TypeSpec.Http;
using KPool.Common;

namespace KPool.WikiPrivateApi;

@doc("Request body for uploading an image.")
model UploadImageRequestBody {
  @doc("Published image identifier to replace.")
  publishedImageIdentifier?: Uuid;
  @doc("Resource type that the image belongs to.")
  resourceType: string;
  @doc("Wiki identifier that the image belongs to.")
  wikiIdentifier: Uuid;
  @doc("Image data encoded as base64.")
  base64EncodedImage: string;
  @doc("Usage category of the image.")
  imageUsage: string;
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
}

@doc("Request body for creating an image hide request.")
model RequestImageHideRequestBody {
  @doc("Name of the requester.")
  requesterName: string;
  @doc("Email address of the requester.")
  requesterEmail: string;
  @doc("Reason for requesting the image to be hidden.")
  reason: string;
}

@doc("Request body for reviewing an image hide request.")
model ReviewImageHideRequestBody {
  @doc("Comment from the reviewer.")
  reviewerComment: string;
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

@doc("Response returned when an image hide request is created.")
model CreateImageHideRequestResponse {
  @statusCode statusCode: 201;
  @body body: ImageHideRequestSummary;
}

@doc("Response returned when an image hide request is reviewed.")
model ReviewImageHideRequestResponse {
  @statusCode statusCode: 200;
  @body body: ImageHideReviewSummary;
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
  @route("/{imageId}/request-hide")
  @doc("Request that an image be hidden.")
  requestImageHide(
    @path imageId: Uuid,
    @body body: RequestImageHideRequestBody,
  ): CreateImageHideRequestResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{imageId}/approve-hide-request")
  @doc("Approve an image hide request.")
  approveImageHideRequest(
    @path imageId: Uuid,
    @body body: ReviewImageHideRequestBody,
  ): ReviewImageHideRequestResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{imageId}/reject-hide-request")
  @doc("Reject an image hide request.")
  rejectImageHideRequest(
    @path imageId: Uuid,
    @body body: ReviewImageHideRequestBody,
  ): ReviewImageHideRequestResponse | KPool.Common.ForbiddenProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
