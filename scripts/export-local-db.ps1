# Export local Docker MySQL (cloudrobe docker-compose) to var/local-db-export.sql
$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
$out = Join-Path $root "var\local-db-export.sql"

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    throw "Docker is required. Start Docker Desktop and run: docker compose up -d mysql"
}

$container = docker ps --filter "name=cloudrobe-mysql" --format "{{.Names}}" | Select-Object -First 1
if (-not $container) {
    throw "MySQL container not running. From cloudrobe folder run: docker compose up -d mysql"
}

New-Item -ItemType Directory -Force -Path (Split-Path $out) | Out-Null

docker exec $container mysqldump `
    -u test_demo_user -ptest_demo_password `
    test_demo_database `
    --single-transaction `
    --no-tablespaces `
    --routines `
    --triggers `
    --result-file=/tmp/local-db-export.sql

docker cp "${container}:/tmp/local-db-export.sql" $out
Write-Host "Exported to $out ($((Get-Item $out).Length) bytes)"
