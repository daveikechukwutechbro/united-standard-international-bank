<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GraphqlSchemaCheckCommand extends Command
{
    protected $signature = 'graphql:schema-check
        {--show-types : Show all defined types}
        {--show-queries : Show all defined queries}
        {--show-mutations : Show all defined mutations}';

    protected $description = 'Validate GraphQL schema consistency and report coverage';

    public function handle(): int
    {
        $this->info('Checking GraphQL schema consistency...');

        $schemaDir = base_path('graphql');

        if (! File::isDirectory($schemaDir)) {
            $this->error('GraphQL schema directory not found.');

            return self::FAILURE;
        }

        $files = File::glob("{$schemaDir}/*.graphql");
        $types = [];
        $queries = [];
        $mutations = [];
        $subscriptions = [];
        $imports = [];
        $errors = [];

        foreach ($files as $file) {
            $content = File::get($file);
            $filename = basename($file);

            // Extract types
            preg_match_all('/^type\s+(\w+)/m', $content, $typeMatches);
            foreach ($typeMatches[1] as $type) {
                $types[$type] = $filename;
            }

            // Extract queries
            preg_match_all('/^\s+(\w+)\s*\(/m', $content, $queryMatches);
            if (str_contains($content, 'extend type Query')) {
                foreach ($queryMatches[1] as $query) {
                    if (! in_array($query, ['page', 'first', 'input', 'id'])) {
                        $queries[$query] = $filename;
                    }
                }
            }

            // Extract mutations
            if (str_contains($content, 'extend type Mutation')) {
                preg_match_all('/^\s+(\w+)\s*\(/m', $content, $mutationMatches);
                foreach ($mutationMatches[1] as $mutation) {
                    if (! in_array($mutation, ['page', 'first', 'input', 'id'])) {
                        $mutations[$mutation] = $filename;
                    }
                }
            }

            // Extract subscriptions
            if (str_contains($content, 'extend type Subscription')) {
                preg_match_all('/^\s+(\w+)\s*[:(]/m', $content, $subMatches);
                foreach ($subMatches[1] as $sub) {
                    $subscriptions[$sub] = $filename;
                }
            }

            // Check for @guard directive
            if (str_contains($content, 'extend type Query') || str_contains($content, 'extend type Mutation')) {
                $queryCount = substr_count($content, '@find') + substr_count($content, '@paginate') + substr_count($content, '@field(resolver:');
                $guardCount = substr_count($content, '@guard');

                if ($queryCount > $guardCount) {
                    $errors[] = "Warning: {$filename} may have unguarded operations ({$queryCount} operations, {$guardCount} guards)";
                }
            }

            // Extract imports
            preg_match_all('/#import\s+(.+)/', $content, $importMatches);
            foreach ($importMatches[1] as $import) {
                $imports[] = trim($import);
            }
        }

        // Check imported files exist
        foreach ($imports as $import) {
            if (! File::exists("{$schemaDir}/{$import}")) {
                $errors[] = "Error: Imported file not found: {$import}";
            }
        }

        // Summary table
        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Schema files', (string) count($files)],
                ['Types defined', (string) count($types)],
                ['Queries', (string) count($queries)],
                ['Mutations', (string) count($mutations)],
                ['Subscriptions', (string) count($subscriptions)],
                ['Imports', (string) count($imports)],
            ]
        );

        if ($this->option('show-types')) {
            $this->newLine();
            $this->info('Defined Types:');
            $this->table(
                ['Type', 'Schema File'],
                collect($types)->map(fn ($file, $type) => [$type, $file])->values()->toArray()
            );
        }

        if ($this->option('show-queries')) {
            $this->newLine();
            $this->info('Defined Queries:');
            $this->table(
                ['Query', 'Schema File'],
                collect($queries)->map(fn ($file, $query) => [$query, $file])->values()->toArray()
            );
        }

        if ($this->option('show-mutations')) {
            $this->newLine();
            $this->info('Defined Mutations:');
            $this->table(
                ['Mutation', 'Schema File'],
                collect($mutations)->map(fn ($file, $mutation) => [$mutation, $file])->values()->toArray()
            );
        }

        if (! empty($errors)) {
            $this->newLine();
            $this->warn('Issues found:');
            foreach ($errors as $error) {
                $this->line("  • {$error}");
            }
        }

        if (empty($errors)) {
            $this->newLine();
            $this->info('Schema check passed. No issues found.');

            return self::SUCCESS;
        }

        return self::SUCCESS;
    }
}
