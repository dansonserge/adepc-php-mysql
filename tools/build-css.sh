#!/usr/bin/env bash
# Compiles the site and admin stylesheets with the Tailwind 4.3.3 standalone
# CLI (a single binary, no Node). Dev-only: the compiled CSS is committed.
set -euo pipefail
cd "$(dirname "$0")/.."
TW=tools/bin/tailwindcss
if [ ! -x "$TW" ]; then
  case "$(uname -s)-$(uname -m)" in
    Darwin-arm64) asset=tailwindcss-macos-arm64 ;;
    Darwin-x86_64) asset=tailwindcss-macos-x64 ;;
    Linux-x86_64) asset=tailwindcss-linux-x64 ;;
    Linux-aarch64) asset=tailwindcss-linux-arm64 ;;
    *) echo "Unsupported platform" >&2; exit 1 ;;
  esac
  mkdir -p tools/bin
  curl -sSL -o "$TW" "https://github.com/tailwindlabs/tailwindcss/releases/download/v4.3.3/$asset"
  chmod +x "$TW"
fi
"$TW" -i resources/css/site.css -o public/assets/css/site.css --minify
if [ -f resources/css/admin.css ]; then
  "$TW" -i resources/css/admin.css -o public/assets/css/admin.css --minify
fi
