$path = 'c:\wamp64\www\ebmis\app\Services\ClientLoanScoringService.php'
$lines = Get-Content $path -Encoding UTF8

# Line 171 (index 170): strip everything after 0.0; on the $dscr line
$lines[170] = '        $dscr = $rli > 0 ? round($vncf / $rli, 2) : 0.0;'

# Remove lines 172-178 (indices 171-177): externalPerPeriod block + blank + old DSCR comment + totalDebt + old $dscr + blank
# After fix, indices 171-177 are the old external block to delete
$newLines = [System.Collections.Generic.List[string]]::new()
for ($i = 0; $i -lt $lines.Count; $i++) {
    if ($i -ge 171 -and $i -le 178) {
        continue  # skip the old external/totalDebt/old-dscr lines
    }
    $newLines.Add($lines[$i])
}

[System.IO.File]::WriteAllLines($path, $newLines, [System.Text.UTF8Encoding]::new($false))
Write-Host "Done. Lines written: $($newLines.Count)"
