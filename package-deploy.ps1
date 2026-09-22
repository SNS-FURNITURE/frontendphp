# PowerShell script to package the Laravel SNS application for Ethio Telecom Plesk Hosting
$ErrorActionPreference = "Stop"

$destinationZip = Join-Path (Get-Location) "sns-deploy.zip"

if (Test-Path $destinationZip) {
    Write-Host "Removing existing sns-deploy.zip..."
    Remove-Item -Force $destinationZip
}

Write-Host "Building production assets (Vite)..."
npm run build

Write-Host "Creating deployment archive: sns-deploy.zip..."

$includePaths = @(
    "app",
    "bootstrap",
    "config",
    "database",
    "public",
    "resources",
    "routes",
    "storage",
    "vendor",
    ".htaccess",
    "artisan",
    "composer.json",
    ".env.example"
)

# Load compression assemblies
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$zipArchive = [System.IO.Compression.ZipFile]::Open($destinationZip, [System.IO.Compression.ZipArchiveMode]::Create)

try {
    foreach ($item in $includePaths) {
        if (Test-Path $item) {
            $itemObj = Get-Item $item
            if ($itemObj.PSIsContainer) {
                $files = Get-ChildItem -Path $item -Recurse -File
                foreach ($file in $files) {
                    $relative = (Resolve-Path $file.FullName -Relative).TrimStart(".\")
                    $entryName = $relative.Replace("\", "/")
                    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zipArchive, $file.FullName, $entryName) | Out-Null
                }
            } else {
                $entryName = $itemObj.Name
                [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zipArchive, $itemObj.FullName, $entryName) | Out-Null
            }
        }
    }
} finally {
    $zipArchive.Dispose()
}

$zipSize = (Get-Item $destinationZip).Length / 1MB
Write-Host ("Deployment package created successfully: sns-deploy.zip ({0:N2} MB)" -f $zipSize)
