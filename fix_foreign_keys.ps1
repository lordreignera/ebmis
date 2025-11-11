# PowerShell script to remove foreign key constraints from SQL dump
param(
    [Parameter(Mandatory=$true)]
    [string]$InputFile
)

$OutputFile = $InputFile -replace '\.sql$', '_no_fk.sql'

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  SQL Foreign Key Constraint Remover" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Input file:  $InputFile" -ForegroundColor Yellow
Write-Host "Output file: $OutputFile" -ForegroundColor Green
Write-Host ""
Write-Host "Processing..." -ForegroundColor Yellow
Write-Host ""

# Read the entire file
$content = Get-Content $InputFile -Raw

# Count original foreign keys
$originalFKCount = ([regex]::Matches($content, "CONSTRAINT.*FOREIGN KEY")).Count
Write-Host "  Found $originalFKCount foreign key constraints" -ForegroundColor Yellow

# Remove all ADD CONSTRAINT ... FOREIGN KEY lines
$content = $content -replace "(?m)^\s*ADD CONSTRAINT ``[^``]+`` FOREIGN KEY.*$", ""

# Remove all CONSTRAINT ... FOREIGN KEY lines (inline in CREATE TABLE)
$content = $content -replace "(?m)^\s*CONSTRAINT ``[^``]+`` FOREIGN KEY.*$", ""

# Remove KEY lines that reference foreign keys (like KEY `table_column_foreign`)
$content = $content -replace "(?m)^\s*KEY ``[^``]+_foreign``.*$", ""

# Clean up extra commas and empty lines
$content = $content -replace "(?m),\s*,", ","  # Remove double commas
$content = $content -replace "(?m),(\s*)\)", ")" # Remove comma before closing parenthesis
$content = $content -replace "(?m)^\s*$\n", ""  # Remove empty lines

# Count remaining foreign keys
$remainingFKCount = ([regex]::Matches($content, "CONSTRAINT.*FOREIGN KEY")).Count

Write-Host ""
Write-Host "  Removed: $($originalFKCount - $remainingFKCount) foreign key constraints" -ForegroundColor Green
Write-Host "  Remaining: $remainingFKCount foreign key constraints" -ForegroundColor $(if($remainingFKCount -eq 0){"Green"}else{"Yellow"})
Write-Host ""
Write-Host "  Writing output file..." -ForegroundColor Yellow

# Write the modified content
$content | Out-File -FilePath $OutputFile -Encoding UTF8 -NoNewline

$originalSize = (Get-Item $InputFile).Length / 1MB
$newSize = (Get-Item $OutputFile).Length / 1MB

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "  SUCCESS! Foreign keys removed" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Output file: $OutputFile" -ForegroundColor Green
Write-Host ""
Write-Host "File size:" -ForegroundColor Cyan
Write-Host "  Original: $([math]::Round($originalSize, 2)) MB" -ForegroundColor Yellow
Write-Host "  New:      $([math]::Round($newSize, 2)) MB" -ForegroundColor Green
Write-Host ""
Write-Host "You can now import this file without foreign key errors:" -ForegroundColor Cyan
Write-Host "  mysql -u root -p ebims1 < $OutputFile" -ForegroundColor White
Write-Host ""
