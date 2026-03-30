#!/bin/bash
#
# Starts the Cloudflare tunnel and outputs the public URL when ready.
# Usage: get-tunnel-url.sh <docker-compose-file>
#

set -e

COMPOSE_FILE="${1:-.github/workflows/templates/docker-compose.yml}"
MAX_ATTEMPTS=30
RETRY_INTERVAL=2

docker compose -f "$COMPOSE_FILE" up -d --quiet-pull tunnel

for i in $(seq 1 $MAX_ATTEMPTS); do
  TUNNEL_URL=$(docker compose -f "$COMPOSE_FILE" logs tunnel | grep -oP 'https://\S*trycloudflare\.com' | head -n 1)
  if [ -n "$TUNNEL_URL" ]; then
    echo "$TUNNEL_URL"
    exit 0
  fi
  echo "Attempt $i/$MAX_ATTEMPTS: waiting for tunnel URL..." >&2
  sleep $RETRY_INTERVAL
done

echo "ERROR: Timed out waiting for tunnel URL after $((MAX_ATTEMPTS * RETRY_INTERVAL))s" >&2
exit 1
