# ============================================
# SQL Dump Fixer for MySQL Import (PowerShell)
# Fixes reserved keywords and syntax issues
# ============================================

param(
    [Parameter(Mandatory=$true)]
    [string]$InputFile
)

Write-Host ""
Write-Host "========================================"
Write-Host "  SQL Dump Fixer for Online Import"
Write-Host "========================================"
Write-Host ""

# Check if file exists
if (-not (Test-Path $InputFile)) {
    Write-Host "ERROR: File not found: $InputFile" -ForegroundColor Red
    Write-Host ""
    exit 1
}

# Generate output filename
$fileInfo = Get-Item $InputFile
$outputFile = Join-Path $fileInfo.DirectoryName "$($fileInfo.BaseName)_fixed$($fileInfo.Extension)"

Write-Host "Input file:  $InputFile" -ForegroundColor Cyan
Write-Host "Output file: $outputFile" -ForegroundColor Green
Write-Host ""
Write-Host "Processing..." -ForegroundColor Yellow
Write-Host ""

try {
    # Read the SQL file
    $content = Get-Content $InputFile -Raw -Encoding UTF8
    
    # Fix groups table references
    Write-Host "  [1/10] Fixing ALTER TABLE groups..." -ForegroundColor Gray
    $content = $content -replace 'ALTER TABLE groups ', 'ALTER TABLE `groups` '
    
    Write-Host "  [2/10] Fixing CREATE TABLE groups..." -ForegroundColor Gray
    $content = $content -replace 'CREATE TABLE groups ', 'CREATE TABLE `groups` '
    
    Write-Host "  [3/10] Fixing INSERT INTO groups..." -ForegroundColor Gray
    $content = $content -replace 'INSERT INTO groups ', 'INSERT INTO `groups` '
    
    Write-Host "  [4/10] Fixing DROP TABLE groups..." -ForegroundColor Gray
    $content = $content -replace 'DROP TABLE groups', 'DROP TABLE `groups`'
    $content = $content -replace 'TABLE IF EXISTS groups', 'TABLE IF EXISTS `groups`'
    
    Write-Host "  [5/10] Fixing REFERENCES groups..." -ForegroundColor Gray
    $content = $content -replace 'REFERENCES groups', 'REFERENCES `groups`'
    
    Write-Host "  [6/10] Fixing FROM groups..." -ForegroundColor Gray
    $content = $content -replace 'FROM groups ', 'FROM `groups` '
    
    Write-Host "  [7/10] Fixing UPDATE groups..." -ForegroundColor Gray
    $content = $content -replace 'UPDATE groups ', 'UPDATE `groups` '
    
    Write-Host "  [8/10] Fixing foreign key names..." -ForegroundColor Gray
    $content = $content -replace 'groups_branch_id_foreign', '`groups_branch_id_foreign`'
    $content = $content -replace 'DROP FOREIGN KEY groups_', 'DROP FOREIGN KEY `groups_'
    
    Write-Host "  [9/10] Fixing LOCK/UNLOCK TABLES..." -ForegroundColor Gray
    $content = $content -replace 'LOCK TABLES groups ', 'LOCK TABLES `groups` '
    $content = $content -replace 'UNLOCK TABLES groups', 'UNLOCK TABLES `groups`'
    
    Write-Host "  [10/10] Writing fixed file..." -ForegroundColor Gray
    Set-Content $outputFile -Value $content -NoNewline -Encoding UTF8
    
    Write-Host ""
    Write-Host "========================================"
    Write-Host "  SUCCESS! Fixed SQL file created"
    Write-Host "========================================"
    Write-Host ""
    Write-Host "Fixed file: $outputFile" -ForegroundColor Green
    Write-Host ""
    Write-Host "File size:"
    Write-Host "  Original: $([math]::Round((Get-Item $InputFile).Length / 1MB, 2)) MB" -ForegroundColor Cyan
    Write-Host "  Fixed:    $([math]::Round((Get-Item $outputFile).Length / 1MB, 2)) MB" -ForegroundColor Green
    Write-Host ""
    Write-Host "You can now import this file to your online database." -ForegroundColor Yellow
    Write-Host ""
    
} catch {
    Write-Host ""
    Write-Host "ERROR: Failed to process file" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    Write-Host ""
    exit 1
}
