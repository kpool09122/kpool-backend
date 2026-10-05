#!/usr/bin/env bash
# Legacy entry point; one pinned toolchain and one validation implementation.
set -euo pipefail
exec bash "$(dirname "$0")/run.sh" check
