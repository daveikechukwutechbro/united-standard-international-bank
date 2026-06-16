<?php

declare(strict_types=1);

use App\Infrastructure\Plugins\PluginSecurityScanner;
use Illuminate\Support\Facades\File;

uses(Tests\TestCase::class);

describe('PluginSecurityScanner', function () {
    it('detects dangerous function calls', function () {
        $testDir = sys_get_temp_dir() . '/plugin-scan-test-' . uniqid();
        File::makeDirectory($testDir, 0755, true);
        File::put("{$testDir}/dangerous.php", <<<'PHP'
<?php
$result = eval('return 1;');
exec('ls -la');
shell_exec('whoami');
PHP);

        $scanner = new PluginSecurityScanner();
        $result = $scanner->scan($testDir);

        expect($result['safe'])->toBeFalse();
        expect($result['issues'])->toHaveCount(3);

        $types = array_column($result['issues'], 'type');
        expect($types)->toContain('eval');
        expect($types)->toContain('exec');
        expect($types)->toContain('shell_exec');

        File::deleteDirectory($testDir);
    });

    it('passes clean plugin code', function () {
        $testDir = sys_get_temp_dir() . '/plugin-scan-clean-' . uniqid();
        File::makeDirectory($testDir, 0755, true);
        File::put("{$testDir}/clean.php", <<<'PHP'
<?php
namespace TestPlugin;

class CleanService
{
    public function calculate(int $a, int $b): int
    {
        return $a + $b;
    }
}
PHP);

        $scanner = new PluginSecurityScanner();
        $result = $scanner->scan($testDir);

        expect($result['safe'])->toBeTrue();
        expect($result['issues'])->toBeEmpty();

        File::deleteDirectory($testDir);
    });

    it('classifies severity correctly', function () {
        $scanner = new PluginSecurityScanner();

        expect($scanner->getSeverity('eval'))->toBe('critical');
        expect($scanner->getSeverity('exec'))->toBe('critical');
        expect($scanner->getSeverity('raw_sql'))->toBe('high');
        expect($scanner->getSeverity('env_access'))->toBe('high');
        expect($scanner->getSeverity('file_get_contents'))->toBe('medium');
        expect($scanner->getSeverity('unknown'))->toBe('low');
    });

    it('summarizes scan results', function () {
        $scanner = new PluginSecurityScanner();

        $issues = [
            ['file' => 'a.php', 'line' => 1, 'type' => 'eval', 'code' => 'eval()'],
            ['file' => 'b.php', 'line' => 2, 'type' => 'eval', 'code' => 'eval()'],
            ['file' => 'c.php', 'line' => 3, 'type' => 'exec', 'code' => 'exec()'],
        ];

        $summary = $scanner->summarize($issues);

        expect($summary['eval'])->toBe(2);
        expect($summary['exec'])->toBe(1);
    });

    it('handles non-existent directory gracefully', function () {
        $scanner = new PluginSecurityScanner();
        $result = $scanner->scan('/non/existent/path');

        expect($result['safe'])->toBeTrue();
        expect($result['issues'])->toBeEmpty();
    });

    it('detects extract, parse_str, call_user_func, and variable includes', function () {
        $testDir = sys_get_temp_dir() . '/plugin-scan-new-patterns-' . uniqid();
        File::makeDirectory($testDir, 0755, true);
        File::put("{$testDir}/risky.php", <<<'PHP'
<?php
extract($_POST);
parse_str($queryString, $params);
call_user_func($callback, $arg);
call_user_func_array($callback, $args);
include $userFile;
require_once $dynamicPath;
PHP);

        $scanner = new PluginSecurityScanner();
        $result = $scanner->scan($testDir);

        expect($result['safe'])->toBeFalse();

        $types = array_column($result['issues'], 'type');
        expect($types)->toContain('extract');
        expect($types)->toContain('parse_str');
        expect($types)->toContain('call_user_func');
        expect($types)->toContain('variable_include');

        File::deleteDirectory($testDir);
    });

    it('classifies new pattern severities correctly', function () {
        $scanner = new PluginSecurityScanner();

        expect($scanner->getSeverity('variable_include'))->toBe('critical');
        expect($scanner->getSeverity('extract'))->toBe('high');
        expect($scanner->getSeverity('call_user_func'))->toBe('high');
        expect($scanner->getSeverity('parse_str'))->toBe('medium');
    });
});
