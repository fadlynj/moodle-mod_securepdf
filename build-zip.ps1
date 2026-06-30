# Package the Secure PDF Moodle plugin as mod_securepdf.zip in Downloads.
# Moodle requires the plugin to live inside a folder named after the plugin
# ("securepdf"), so the codebase is copied into <staging>/securepdf first.

$ErrorActionPreference = 'Stop'

$source      = $PSScriptRoot
$pluginname  = 'securepdf'
$zipname     = 'mod_securepdf.zip'
$downloads   = Join-Path $env:USERPROFILE 'Downloads'
$zippath     = Join-Path $downloads $zipname

# Make sure Downloads exists.
if (-not (Test-Path $downloads)) {
    New-Item -ItemType Directory -Path $downloads -Force | Out-Null
}

# Fresh staging dir: <temp>/securepdf_build_<guid>/securepdf
$staging = Join-Path ([System.IO.Path]::GetTempPath()) ("securepdf_build_" + [guid]::NewGuid().ToString('N'))
$dest    = Join-Path $staging $pluginname
New-Item -ItemType Directory -Path $dest -Force | Out-Null

# Copy the codebase, excluding VCS, editor and build artifacts.
robocopy $source $dest /E /XD .git .claude /XF $zipname 'build-zip.ps1' 'build-zip.sh' '*.zip' .gitignore | Out-Null
# robocopy uses bitmapped exit codes; >= 8 means a real failure.
if ($LASTEXITCODE -ge 8) {
    throw "robocopy failed with exit code $LASTEXITCODE"
}

# (Re)create the zip with the securepdf/ folder at its root.
# Build entries by hand and force forward-slash separators: both
# Compress-Archive and ZipFile::CreateFromDirectory write backslashes on
# Windows PowerShell, which Moodle's plugin installer cannot read as a folder
# tree ("cannot detect plugin type / jenis pengaya"). Entry names are taken
# relative to $staging, so each one starts with "securepdf/".
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
if (Test-Path $zippath) {
    Remove-Item $zippath -Force
}
$archive = [System.IO.Compression.ZipFile]::Open($zippath, 'Create')
try {
    $baselen = $staging.Length + 1
    Get-ChildItem -Path $dest -Recurse -File | ForEach-Object {
        $entryname = $_.FullName.Substring($baselen) -replace '\\', '/'
        [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
            $archive, $_.FullName, $entryname)
    }
} finally {
    $archive.Dispose()
}

# Clean up staging.
Remove-Item $staging -Recurse -Force

Write-Output "Created $zippath"
