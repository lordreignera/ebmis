<?php
$path = __DIR__ . '/../resources/views/admin/sidebar_new.blade.php';
$content = file($path);
$stack = [];
$lineNo = 0;
foreach ($content as $line) {
    $lineNo++;
    // remove strings inside {{-- --}} comments
    $trim = trim($line);
    // Skip Laravel Blade comments
    if (str_starts_with($trim, '{{--')) continue;
    // find occurrences of @if, @elseif, @else, @endif in line
    if (strpos($line, '@if') !== false) {
        // ensure not part of @endif
        if (strpos($line, '@endif') === false) {
            $stack[] = ['type'=>'if','line'=>$lineNo,'text'=>trim($line)];
        }
    }
    if (strpos($line, '@foreach') !== false) {
        $stack[] = ['type'=>'foreach','line'=>$lineNo,'text'=>trim($line)];
    }
    if (strpos($line, '@section') !== false) {
        $stack[] = ['type'=>'section','line'=>$lineNo,'text'=>trim($line)];
    }
    if (strpos($line, '@endif') !== false) {
        // pop last if
        $found = false;
        for ($i=count($stack)-1;$i>=0;$i--) {
            if ($stack[$i]['type']=='if') { array_splice($stack,$i,1); $found=true; break; }
        }
        if (!$found) echo "Unmatched @endif at $lineNo\n";
    }
    if (strpos($line, '@endforeach') !== false) {
        $found = false;
        for ($i=count($stack)-1;$i>=0;$i--) {
            if ($stack[$i]['type']=='foreach') { array_splice($stack,$i,1); $found=true; break; }
        }
        if (!$found) echo "Unmatched @endforeach at $lineNo\n";
    }
    if (strpos($line, '@endsection') !== false) {
        $found = false;
        for ($i=count($stack)-1;$i>=0;$i--) {
            if ($stack[$i]['type']=='section') { array_splice($stack,$i,1); $found=true; break; }
        }
        if (!$found) echo "Unmatched @endsection at $lineNo\n";
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
