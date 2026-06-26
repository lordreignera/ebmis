<?php
$dir = __DIR__ . '/../resources/views';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$patterns = [
    ['open'=>'@if','close'=>'@endif'],
    ['open'=>'@foreach','close'=>'@endforeach'],
    ['open'=>'@section','close'=>'@endsection'],
    ['open'=>'@isset','close'=>'@endisset'],
    ['open'=>'@auth','close'=>'@endauth'],
    ['open'=>'@guest','close'=>'@endguest'],
    ['open'=>'@forelse','close'=>'@endforelse'],
];
$problems = [];
foreach ($it as $file) {
    if (!$file->isFile()) continue;
    if (pathinfo($file->getFilename(), PATHINFO_EXTENSION) !== 'php') continue;
    $path = $file->getPathname();
    if (!str_ends_with($path, '.blade.php')) continue;
    $content = file_get_contents($path);
    foreach ($patterns as $p) {
        $openCount = substr_count($content, $p['open']);
        $closeCount = substr_count($content, $p['close']);
        if ($openCount !== $closeCount) {
            $problems[] = [$path, $p['open'], $openCount, $p['close'], $closeCount];
        }
    }
}
if (empty($problems)) {
    echo "No mismatches found\n";
    exit(0);
}
foreach ($problems as $pr) {
    list($path,$open,$o,$close,$c) = $pr;
    echo "$path : $open=$o, $close=$c\n";
}
exit(0);
