<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\View;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$routes = app('router')->getRoutes();
$invalidActions = [];
$unnamedRoutes = [];
$moduleCounts = [];
$controllerActions = [];

/**
 * Return a useful business-area label for a route URI.
 */
function moduleName(string $uri): string
{
    $segments = array_values(array_filter(explode('/', trim($uri, '/')), 'strlen'));

    if (($segments[0] ?? null) === 'admin') {
        return $segments[1] ?? 'admin-home';
    }

    if (($segments[0] ?? null) === 'school') {
        return 'school:'.($segments[1] ?? 'dashboard');
    }

    if (($segments[0] ?? null) === 'api') {
        return 'api:'.($segments[1] ?? 'root');
    }

    return $segments[0] ?? 'root';
}

/**
 * Validate controller-backed route actions without executing mutations.
 */
function validateAction(Route $route): ?array
{
    $action = $route->getActionName();

    if ($action === 'Closure') {
        return null;
    }

    [$class, $method] = str_contains($action, '@')
        ? explode('@', $action, 2)
        : [$action, '__invoke'];

    if (! class_exists($class)) {
        return ['name' => $route->getName(), 'methods' => $route->methods(), 'uri' => $route->uri(), 'action' => $action, 'reason' => 'Controller class does not exist'];
    }

    if (! method_exists($class, $method)) {
        return ['name' => $route->getName(), 'methods' => $route->methods(), 'uri' => $route->uri(), 'action' => $action, 'reason' => 'Controller method does not exist'];
    }

    return null;
}

foreach ($routes as $route) {
    $module = moduleName($route->uri());
    $moduleCounts[$module] = ($moduleCounts[$module] ?? 0) + 1;

    if ($route->getName() === null) {
        $unnamedRoutes[] = $route->uri();
    }

    if (($issue = validateAction($route)) !== null) {
        $invalidActions[] = $issue;
    }

    if ($route->getActionName() !== 'Closure') {
        $controllerActions[$route->getActionName()] = true;
    }
}

ksort($moduleCounts);

$viewRoot = resource_path('views');
$viewFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewRoot));
$bladeViews = 0;
$missingViewReferences = [];

foreach ($viewFiles as $file) {
    if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
        continue;
    }

    $bladeViews++;
    $contents = file_get_contents($file->getPathname());

    preg_match_all("/(?:view|extends|include|component)\\(\\s*['\"]([^'\"]+)['\"]/", $contents, $matches);

    foreach ($matches[1] ?? [] as $referencedView) {
        // Laravel mail components use a registered component namespace rather
        // than ordinary application view lookup.
        if (str_contains($referencedView, '::')) {
            continue;
        }

        if (! View::exists($referencedView)) {
            $missingViewReferences[] = [
                'file' => str_replace('\\\\', '/', $file->getPathname()),
                'view' => $referencedView,
            ];
        }
    }
}

$report = [
    'generated_at' => now()->toIso8601String(),
    'routes' => [
        'total' => count($routes),
        'named' => count($routes) - count($unnamedRoutes),
        'unnamed' => count($unnamedRoutes),
        'unique_controller_actions' => count($controllerActions),
        'invalid_controller_actions' => $invalidActions,
        'module_counts' => $moduleCounts,
    ],
    'views' => [
        'blade_files' => $bladeViews,
        'missing_static_references' => $missingViewReferences,
    ],
];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;

exit(($invalidActions === [] && $missingViewReferences === []) ? 0 : 1);
