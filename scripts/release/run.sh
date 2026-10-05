#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/../.."
release_python="${RELEASE_PYTHON:-${CFN_VENV:-/tmp/kpool-cfn-tools}/bin/python}"
release_actionlint="${ACTIONLINT:-actionlint}"
case "${1:-check}" in
  test) "$release_python" -B -m unittest discover -s scripts/release -p 'test_*.py' -v ;;
  lint)
    "$release_actionlint" -version
    "$release_actionlint" -shellcheck='' .github/workflows/release.yml .github/workflows/backend-*.yml
    ;;
  check)
    bash scripts/release/run.sh test
    bash scripts/release/run.sh lint
    bash scripts/cloudformation/run.sh check
    ;;
  *) echo 'Usage: run.sh {test|lint|check}' >&2; exit 2 ;;
esac
