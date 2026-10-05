#!/usr/bin/env bash
set -euo pipefail

source_directory="${1:?Source checkout directory is required}"
if [[ ! "${SOURCE_SHA:-}" =~ ^[0-9a-f]{40}$ ]]; then
  echo 'Source must be a full lowercase commit SHA' >&2
  exit 1
fi

# The caller checks out fixed main with its complete history, never an input ref.
main_sha="$(git -C "$source_directory" rev-parse refs/remotes/origin/main)"
if [[ "$(git -C "$source_directory" rev-parse HEAD)" != "$main_sha" ]] ||
   [[ "$(git -C "$source_directory" rev-parse --is-shallow-repository)" != false ]]; then
  echo 'Source checkout must start at origin/main with complete history' >&2
  exit 1
fi
if [[ "$(git -C "$source_directory" cat-file -t "$SOURCE_SHA")" != commit ]] ||
   ! git -C "$source_directory" merge-base --is-ancestor "$SOURCE_SHA" "$main_sha"; then
  echo 'Pinned source commit is not in the checked-out main history' >&2
  exit 1
fi

# Only an already fetched, validated ancestor can become executable source.
git -C "$source_directory" -c core.hooksPath=/dev/null checkout --detach "$SOURCE_SHA"
[[ "$(git -C "$source_directory" rev-parse HEAD)" == "$SOURCE_SHA" ]]
