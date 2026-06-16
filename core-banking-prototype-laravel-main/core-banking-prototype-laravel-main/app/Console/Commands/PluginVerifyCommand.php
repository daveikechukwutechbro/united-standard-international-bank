<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PluginVerifyCommand extends Command
{
    protected $signature = 'plugin:verify
        {name? : The plugin name to verify (vendor/name format)}
        {--all : Verify all installed plugins}';

    protected $description = 'Verify plugin integrity and security';

    public function handle(): int
    {
        $pluginDir = base_path('plugins');

        if (! File::isDirectory($pluginDir)) {
            $this->error('No plugins directory found.');

            return self::FAILURE;
        }

        $plugins = $this->option('all')
            ? File::directories($pluginDir)
            : ($this->argument('name')
                ? ["{$pluginDir}/{$this->argument('name')}"]
                : File::directories($pluginDir));

        if (empty($plugins)) {
            $this->info('No plugins found to verify.');

            return self::SUCCESS;
        }

        $hasFailures = false;

        foreach ($plugins as $pluginPath) {
            $pluginName = basename($pluginPath);
            $this->info("Verifying plugin: {$pluginName}");

            $checks = $this->runChecks($pluginPath, $pluginName);

            $this->table(
                ['Check', 'Status', 'Details'],
                $checks
            );

            $failed = collect($checks)->filter(fn ($check) => $check[1] === 'FAIL');
            if ($failed->isNotEmpty()) {
                $hasFailures = true;
                $this->warn("{$pluginName}: {$failed->count()} check(s) failed.");
            } else {
                $this->info("{$pluginName}: All checks passed.");
            }

            $this->newLine();
        }

        return $hasFailures ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function runChecks(string $pluginPath, string $pluginName): array
    {
        $checks = [];

        // Check manifest exists
        $manifestPath = "{$pluginPath}/manifest.json";
        if (File::exists($manifestPath)) {
            $checks[] = ['Manifest exists', 'PASS', 'manifest.json found'];

            $manifest = json_decode(File::get($manifestPath), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $checks[] = ['Manifest valid JSON', 'PASS', 'Valid JSON structure'];

                // Check required fields
                $required = ['name', 'vendor', 'version', 'description', 'type'];
                $missing = array_diff($required, array_keys($manifest ?? []));
                if (empty($missing)) {
                    $checks[] = ['Required fields', 'PASS', 'All required fields present'];
                } else {
                    $checks[] = ['Required fields', 'FAIL', 'Missing: ' . implode(', ', $missing)];
                }

                // Version format
                if (isset($manifest['version']) && preg_match('/^\d+\.\d+\.\d+$/', $manifest['version'])) {
                    $checks[] = ['Version format', 'PASS', "v{$manifest['version']}"];
                } else {
                    $checks[] = ['Version format', 'FAIL', 'Invalid semver format'];
                }
            } else {
                $checks[] = ['Manifest valid JSON', 'FAIL', json_last_error_msg()];
            }
        } else {
            $checks[] = ['Manifest exists', 'FAIL', 'manifest.json not found'];
        }

        // Check entry point
        if (isset($manifest['entry_point'])) {
            $entryFile = "{$pluginPath}/{$manifest['entry_point']}.php";
            if (File::exists($entryFile)) {
                $checks[] = ['Entry point', 'PASS', "{$manifest['entry_point']}.php found"];
            } else {
                $checks[] = ['Entry point', 'FAIL', "{$manifest['entry_point']}.php not found"];
            }
        }

        // Security checks
        $phpFiles = File::glob("{$pluginPath}/*.php");
        $securityIssues = [];

        foreach ($phpFiles as $file) {
            $content = File::get($file);

            if (preg_match('/\b(eval|exec|system|passthru|shell_exec|popen|proc_open)\s*\(/', $content)) {
                $securityIssues[] = basename($file) . ': dangerous function call detected';
            }

            if (preg_match('/\b(file_put_contents|file_get_contents)\s*\(/', $content) && ! isset($manifest['permissions']['filesystem'])) {
                $securityIssues[] = basename($file) . ': filesystem access without permission';
            }
        }

        if (empty($securityIssues)) {
            $checks[] = ['Security scan', 'PASS', 'No issues detected'];
        } else {
            foreach ($securityIssues as $issue) {
                $checks[] = ['Security scan', 'FAIL', $issue];
            }
        }

        // Check for README or docs
        $hasReadme = File::exists("{$pluginPath}/README.md") || File::exists("{$pluginPath}/readme.md");
        $checks[] = ['Documentation', $hasReadme ? 'PASS' : 'WARN', $hasReadme ? 'README.md found' : 'No documentation'];

        return $checks;
    }
}
