#!/usr/bin/env bash

set -euo pipefail

if [[ $# -ne 1 ]]; then
  echo "usage: $0 <frontend-repository-path>" >&2
  exit 1
fi

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
FRONTEND_DIR="$1"
OUTPUT_DIR="${FRONTEND_DIR}/packages/types/src"
OPENAPI_ZOD_CLIENT_VERSION="${OPENAPI_ZOD_CLIENT_VERSION:-1.18.3}"

if [[ -n "${OPENAPI_ZOD_CLIENT_BIN:-}" ]]; then
  GENERATOR_CMD=("${OPENAPI_ZOD_CLIENT_BIN}")
else
  GENERATOR_CMD=(npx --yes "openapi-zod-client@${OPENAPI_ZOD_CLIENT_VERSION}")
fi

if [[ ! -d "${FRONTEND_DIR}" ]]; then
  echo "frontend directory does not exist: ${FRONTEND_DIR}" >&2
  exit 1
fi

mkdir -p "${OUTPUT_DIR}"

declare -a GENERATION_TARGETS=(
  "KPool.AccountApi.openapi.yaml:account-api.ts:accountApi"
  "KPool.IdentityApi.openapi.yaml:identity-api.ts:identityApi"
  "KPool.MonetizationApi.openapi.yaml:monetization-api.ts:monetizationApi"
  "KPool.WebhookApi.openapi.yaml:webhook-api.ts:webhookApi"
  "KPool.WikiPrivateApi.openapi.yaml:wiki-private-api.ts:wikiPrivateApi"
)

for target in "${GENERATION_TARGETS[@]}"; do
  IFS=":" read -r source_file output_file api_client_name <<< "${target}"
  "${GENERATOR_CMD[@]}" \
    "${ROOT_DIR}/doc/openapi/${source_file}" \
    -o "${OUTPUT_DIR}/${output_file}" \
    --api-client-name "${api_client_name}" \
    --export-schemas \
    --with-docs
done
