#!/usr/bin/env bash
set -euo pipefail
source_directory="${1:?Source checkout directory is required}"
if [[ ! "${SOURCE_SHA:-}" =~ ^[0-9a-f]{40}$ ]]; then
  echo 'Source must be a full lowercase commit SHA' >&2
  exit 1
fi
# The API check and immutable plan precede checkout; verify before running app code.
if [[ "$(git -C "$source_directory" rev-parse HEAD)" != "$SOURCE_SHA" ]] ||
   [[ "$(git -C "$source_directory" cat-file -t "$SOURCE_SHA")" != commit ]]; then
  echo 'Source checkout differs from the pinned commit' >&2
  exit 1
fi
