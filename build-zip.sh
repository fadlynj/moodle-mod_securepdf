#!/usr/bin/env bash
# Package the Secure PDF Moodle plugin as mod_securepdf.zip in ~/Downloads.
# Linux / macOS counterpart of build-zip.ps1.
#
# Moodle requires the plugin to live inside a folder named after the plugin
# ("securepdf"), so the codebase is copied into <staging>/securepdf first.
# The `zip` tool writes forward-slash separators, which Moodle's installer
# needs to detect the plugin type.
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
plugin="securepdf"
zipname="mod_securepdf.zip"
downloads="${HOME}/Downloads"
zippath="${downloads}/${zipname}"

command -v zip >/dev/null 2>&1 || {
    echo "Error: 'zip' is not installed. Install it (e.g. 'sudo apt install zip' or 'brew install zip')." >&2
    exit 1
}

mkdir -p "$downloads"

# Fresh staging dir: <temp>/securepdf
staging="$(mktemp -d)"
trap 'rm -rf "$staging"' EXIT
dest="${staging}/${plugin}"
mkdir -p "$dest"

# Copy the codebase, excluding VCS, editor and build artifacts.
excludes=(--exclude='.git' --exclude='.claude' --exclude='.gitignore'
          --exclude="$zipname" --exclude='build-zip.ps1'
          --exclude='build-zip.sh' --exclude='*.zip')

if command -v rsync >/dev/null 2>&1; then
    rsync -a "${excludes[@]}" "${script_dir}/" "${dest}/"
else
    # Fallback: copy everything, then delete the unwanted bits.
    cp -a "${script_dir}/." "${dest}/"
    rm -rf "${dest}/.git" "${dest}/.claude" "${dest}/.gitignore" \
           "${dest}/build-zip.ps1" "${dest}/build-zip.sh" "${dest}/${zipname}"
    find "${dest}" -maxdepth 1 -name '*.zip' -delete
fi

# (Re)create the zip with the securepdf/ folder at its root.
rm -f "$zippath"
( cd "$staging" && zip -r -X "$zippath" "$plugin" >/dev/null )

echo "Created $zippath"
