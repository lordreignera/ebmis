<?php
$path = $argv[1] ?? __DIR__ . '/../resources/views/admin/sidebar_new.blade.php';
if (!file_exists($path)) { echo "File not found: $path\n"; exit(2); }
$content = file_get_contents($path);
// remove Blade comments {{-- ... --}} (multiline)
$content = preg_replace('/\{\-\-.*?\-\-\}/s', '', $content);
$lines = preg_split("/\R/", $content);
$stack = [];
$lineNo = 0;
foreach ($lines as $line) {
    $lineNo++;
    // find all @if, @elseif, @else, @endif occurrences
    preg_match_all('/@(if|elseif|else|endif)(?![A-Za-z0-9_])/', $line, $matches, PREG_SET_ORDER);
    foreach ($matches as $m) {
        $dir = $m[1];
        if ($dir === 'if') {
            $stack[] = ['type'=>'if','line'=>$lineNo,'text'=>trim($line)];
        } elseif ($dir === 'endif') {
            $found = false;
            for ($i = count($stack)-1; $i>=0; $i--) {
                if ($stack[$i]['type'] === 'if') { array_splice($stack,$i,1); $found=true; break; }
            }
            if (!$found) {
                echo "Unmatched @endif at $lineNo\n";
            }
        } else {
            // elseif/else do not change stack
        }
    }
}
if (!empty($stack)) {
    echo "Remaining stack (unclosed directives):\n";
    foreach ($stack as $s) {
        echo "- {$s['type']} opened at line {$s['line']}: {$s['text']}\n";
    }
} else {
    echo "All directives closed.\n";
}
// Also print counts
$ifCount = preg_match_all('/@if(?![A-Za-z0-9_])/', $content, $m1);
$endifCount = preg_match_all('/@endif(?![A-Za-z0-9_])/', $content, $m2);
echo "Counts: @if={$ifCount}, @endif={$endifCount}\n";
?>