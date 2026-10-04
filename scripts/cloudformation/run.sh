#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/../.."
CFN_VENV="${CFN_VENV:-/tmp/kpool-cfn-tools}"
case "${1:-check}" in
  install)
    "${CFN_PYTHON:-python3.13}" -m venv "$CFN_VENV"
    "$CFN_VENV/bin/python" -m pip install -r scripts/cloudformation/requirements.txt
    ;;
  lint)
    "$CFN_VENV/bin/cfn-lint" --version
    "$CFN_VENV/bin/cfn-lint" --regions ap-northeast-1 --non-zero-exit-code warning --template infra/cloudformation/*.yaml
    ;;
  test)
    "$CFN_VENV/bin/python" -B -m unittest discover -s scripts/cloudformation -p 'test_*.py' -v
    ;;
  check)
    bash scripts/cloudformation/run.sh lint
    bash scripts/cloudformation/run.sh test
    ;;
  *) echo 'Usage: run.sh {install|lint|test|check}' >&2; exit 2 ;;
esac
