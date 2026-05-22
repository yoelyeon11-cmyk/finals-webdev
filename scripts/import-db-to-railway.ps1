# Import var/local-db-export.sql into Railway MySQL (use MYSQL_PUBLIC_URL from Railway dashboard)
param(
    [string]$SqlFile = "",
    [string]$DatabaseUrl = $env:RAILWAY_DATABASE_URL
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
if (-not $SqlFile) { $SqlFile = Join-Path $root "var\local-db-export.sql" }

if (-not $DatabaseUrl) {
    Write-Host @"
RAILWAY_DATABASE_URL is not set.

1. Railway → MySQL service → Variables → copy MYSQL_PUBLIC_URL
2. In PowerShell:
   `$env:RAILWAY_DATABASE_URL = 'mysql://root:PASSWORD@HOST:PORT/railway'
   .\scripts\import-db-to-railway.ps1
"@
    exit 1
}

if (-not (Test-Path $SqlFile)) {
    throw "SQL file not found: $SqlFile. Run .\scripts\export-local-db.ps1 first."
}

# Parse mysql://user:pass@host:port/db
if ($DatabaseUrl -notmatch '^mysql://([^:]+):([^@]+)@([^:]+):(\d+)/([^?]+)') {
    throw "Invalid DATABASE URL format. Use MYSQL_PUBLIC_URL from Railway."
}
$user = $Matches[1]
$pass = $Matches[2]
$dbHost = $Matches[3]
$port = $Matches[4]
$db   = $Matches[5]

Write-Host "Importing $SqlFile -> Railway ${dbHost}:$port/$db (this replaces existing tables/data)"

Get-Content -Path $SqlFile -Raw | docker run --rm -i mysql:8.0 mysql `
    -h $dbHost -P $port -u $user "-p$pass" $db

if ($LASTEXITCODE -eq 0) {
    Write-Host "Import finished successfully."
} else {
    throw "Import failed with exit code $LASTEXITCODE"
}
