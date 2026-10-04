#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/../.."
cfn-lint --version
cfn-lint --regions ap-northeast-1 --non-zero-exit-code warning --template infra/cloudformation/*.yaml
PYTHONDONTWRITEBYTECODE=1 python3 -m unittest discover -s scripts/cloudformation -p 'test_*.py' -v
