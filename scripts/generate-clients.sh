#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
SPEC_FILE="$ROOT_DIR/openapi/ingestion.yaml"
OUTPUT_DIR="$ROOT_DIR/clients"

if [ ! -f "$SPEC_FILE" ]; then
    echo "Error: OpenAPI spec not found at $SPEC_FILE"
    exit 1
fi

# Check for openapi-generator-cli
if ! command -v openapi-generator-cli &>/dev/null && ! command -v npx &>/dev/null; then
    echo "Error: openapi-generator-cli or npx is required."
    echo "Install with: npm install -g @openapitools/openapi-generator-cli"
    exit 1
fi

GENERATOR_CMD="openapi-generator-cli"
if ! command -v openapi-generator-cli &>/dev/null; then
    GENERATOR_CMD="npx @openapitools/openapi-generator-cli"
fi

echo "=== Generating Python client ==="
$GENERATOR_CMD generate \
    -i "$SPEC_FILE" \
    -g python \
    -o "$OUTPUT_DIR/python" \
    --package-name adp_client \
    --additional-properties=projectName=adp-client,packageVersion=1.0.0 \
    2>&1

echo ""
echo "=== Generating TypeScript client ==="
$GENERATOR_CMD generate \
    -i "$SPEC_FILE" \
    -g typescript-fetch \
    -o "$OUTPUT_DIR/typescript" \
    --additional-properties=npmName=@app-dev-panel/client,npmVersion=1.0.0,supportsES6=true \
    2>&1

echo ""
echo "=== Done ==="
echo "Python client:     $OUTPUT_DIR/python"
echo "TypeScript client:  $OUTPUT_DIR/typescript"
