Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Add-PathEntry {
    param(
        [string]$ExistingPath,
        [string]$Entry
    )

    $parts = @($ExistingPath -split ';' | Where-Object { $_ -and $_.Trim() -ne '' })
    $normalizedEntry = [System.IO.Path]::GetFullPath($Entry).TrimEnd('\')

    foreach ($part in $parts) {
        try {
            if ([System.IO.Path]::GetFullPath($part).TrimEnd('\') -ieq $normalizedEntry) {
                return $ExistingPath
            }
        } catch {
            if ($part.TrimEnd('\') -ieq $normalizedEntry) {
                return $ExistingPath
            }
        }
    }

    if ([string]::IsNullOrWhiteSpace($ExistingPath)) {
        return $normalizedEntry
    }

    return ($ExistingPath.TrimEnd(';') + ';' + $normalizedEntry)
}

$websiteRoot = Split-Path -Parent $PSScriptRoot
$serverRoot = Split-Path -Parent $websiteRoot

$phpDir = Join-Path $serverRoot 'Tools\php7'
$mysqlDir = Join-Path $serverRoot 'Database\bin'

$requiredDirs = @($phpDir, $mysqlDir)
foreach ($dir in $requiredDirs) {
    if (-not (Test-Path -LiteralPath $dir -PathType Container)) {
        throw "Required directory not found: $dir"
    }
}

$sessionPath = $env:PATH
$userPath = [Environment]::GetEnvironmentVariable('Path', 'User')

foreach ($dir in $requiredDirs) {
    $sessionPath = Add-PathEntry -ExistingPath $sessionPath -Entry $dir
    $userPath = Add-PathEntry -ExistingPath $userPath -Entry $dir
}

$env:PATH = $sessionPath
[Environment]::SetEnvironmentVariable('Path', $userPath, 'User')

Write-Host 'Added SPP tool directories to PATH for the current user and this PowerShell session:'
Write-Host "  - $phpDir"
Write-Host "  - $mysqlDir"
Write-Host ''
Write-Host 'Open a new PowerShell window after this if you want the updated PATH outside the current session.'
