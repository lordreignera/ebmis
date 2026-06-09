<?php

namespace App\Console\Commands;

use App\Support\RouteSecurityAudit;
use Illuminate\Console\Command;

class AuditRouteSecurity extends Command
{
    protected $signature = 'security:audit-routes {--matrix : Print the full admin route matrix} {--json : Print the audit as JSON}';

    protected $description = 'Audit route authentication, operational permission mappings, and sensitive Super Admin actions';

    public function handle(RouteSecurityAudit $audit): int
    {
        $summary = $audit->summary();
        $issues = $audit->issues();
        $warnings = $audit->warnings();

        if ($this->option('json')) {
            $this->line(json_encode([
                'summary' => $summary,
                'issues' => $issues->all(),
                'warnings' => $warnings->all(),
                'matrix' => $this->option('matrix') ? $audit->matrix()->all() : null,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $issues->isEmpty() ? self::SUCCESS : self::FAILURE;
        }

        $this->table(['Metric', 'Count'], collect($summary)->map(fn ($value, $key) => [$key, $value])->values()->all());

        if ($this->option('matrix')) {
            $this->table(
                ['Route', 'Methods', 'URI', 'Access', 'Permission'],
                $audit->matrix()
                    ->map(fn (array $route) => [
                        $route['name'],
                        $route['methods'],
                        $route['uri'],
                        $route['access'],
                        $route['permission'] ?? '-',
                    ])
                    ->all()
            );
        }

        foreach ($warnings as $warning) {
            $this->warn($warning);
        }

        if ($issues->isNotEmpty()) {
            $this->error('Route security audit failed.');
            $issues->each(fn (string $issue) => $this->line(" - {$issue}"));

            return self::FAILURE;
        }

        $this->info('Route security audit passed.');

        return self::SUCCESS;
    }
}
