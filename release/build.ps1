# ============================================================================
# HR PRO — build the free-download ZIP.
#
#   pwsh release/build.ps1            -> dist/HR-PRO-V2-<yyyyMMdd>.zip
#
# The ZIP holds the app as it is in the working tree (tracked + new files that
# .gitignore does not exclude — never .env, the database, uploads or logs),
# production dependencies in vendor/ so only PHP is needed, and the kit from
# release/kit/: the Thai guide, the installer and the launchers.
# ============================================================================
$ErrorActionPreference = 'Stop'

$root = Resolve-Path (Join-Path $PSScriptRoot '..')
$name = 'HR-PRO-V2'
$dist = Join-Path $root 'dist'
$stage = Join-Path $dist $name
$zip = Join-Path $dist ("$name-" + (Get-Date).ToString('yyyyMMdd', [Globalization.CultureInfo]::InvariantCulture) + '.zip')

# Development-only material that a downloader has no use for.
$exclude = @(
    '.claude/', '.github/', 'docs/', 'presentation/', 'promo-screenshots/',
    'release/', 'tests/', 'phpunit.xml', '.phpunit.cache/'
)

Write-Host "Staging $stage"
if (Test-Path $dist) { Remove-Item $stage, $zip -Recurse -Force -ErrorAction SilentlyContinue }
New-Item -ItemType Directory -Force $stage | Out-Null

# --- 1. App files --------------------------------------------------------
Push-Location $root
try {
    $files = git -c core.quotepath=off ls-files --cached --others --exclude-standard
    if ($LASTEXITCODE -ne 0) { throw 'git ls-files failed' }
} finally { Pop-Location }

$copied = 0
foreach ($file in $files) {
    if ($exclude | Where-Object { $file -eq $_ -or $file.StartsWith($_) }) { continue }
    $src = Join-Path $root $file
    if (-not (Test-Path -LiteralPath $src -PathType Leaf)) { continue }   # deleted in the working tree
    $dst = Join-Path $stage $file
    New-Item -ItemType Directory -Force (Split-Path $dst) | Out-Null
    Copy-Item -LiteralPath $src -Destination $dst
    $copied++
}
Write-Host "  $copied app files"

# Never ship secrets or local data, whatever .gitignore says.
foreach ($path in '.env', 'database/database.sqlite', 'storage/.seeded', 'public/storage') {
    Remove-Item (Join-Path $stage $path) -Force -Recurse -ErrorAction SilentlyContinue
}

# --- 2. Kit (guide, installer, launchers) --------------------------------
Copy-Item (Join-Path $PSScriptRoot 'kit/*') $stage -Recurse -Force

# Windows batch files need CRLF; shell scripts need LF.
Get-ChildItem $stage -Filter *.bat | ForEach-Object {
    $text = [IO.File]::ReadAllText($_.FullName) -replace "`r?`n", "`r`n"
    [IO.File]::WriteAllText($_.FullName, $text, [Text.UTF8Encoding]::new($false))
}
Get-ChildItem $stage -Filter *.sh -Recurse | ForEach-Object {
    $text = [IO.File]::ReadAllText($_.FullName) -replace "`r`n", "`n"
    [IO.File]::WriteAllText($_.FullName, $text, [Text.UTF8Encoding]::new($false))
}

# --- 3. Production dependencies ------------------------------------------
Write-Host '  composer install --no-dev'
$composer = Join-Path $root 'composer.phar'
$composerArgs = @('install', "--working-dir=$stage", '--no-dev', '--optimize-autoloader', '--no-interaction', '--no-progress', '--quiet')
if (Test-Path $composer) { php $composer @composerArgs } else { composer @composerArgs }
if ($LASTEXITCODE -ne 0) { throw 'composer install failed' }
Remove-Item (Join-Path $stage 'bootstrap/cache/*.php') -Force -ErrorAction SilentlyContinue

# --- 4. Zip ------------------------------------------------------------------
Write-Host "  zipping"
Compress-Archive -Path $stage -DestinationPath $zip -CompressionLevel Optimal -Force

$size = '{0:N1} MB' -f ((Get-Item $zip).Length / 1MB)
Write-Host ""
Write-Host "Built $zip ($size)"
