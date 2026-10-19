#!/usr/bin/env bash
set -euo pipefail
source_directory="${1:?Source checkout directory is required}"
if [[ ! "${SOURCE_SHA:-}" =~ ^[0-9a-f]{40}$ ]]; then
  echo 'Source must be a full lowercase commit SHA' >&2
  exit 1
fi
# Repository and immutable-plan identity have already been checked through the API.
# Checkout fetches main and all tags first, without an input-derived ref.
# Only a fetched commit can become executable source; release tags may be outside main.
if [[ "$(git -C "$source_directory" cat-file -t "$SOURCE_SHA")" != commit ]]; then
  echo 'Pinned source is not a fetched commit' >&2
  exit 1
fi
git -C "$source_directory" -c core.hooksPath=/dev/null checkout --detach "$SOURCE_SHA"
if [[ "$(git -C "$source_directory" rev-parse HEAD)" != "$SOURCE_SHA" ]]; then
  echo 'Source checkout differs from the pinned commit' >&2
  exit 1
fi
