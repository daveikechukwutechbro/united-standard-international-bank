<?php

declare(strict_types=1);

namespace App\Domain\Security\Checks;

use App\Domain\Security\Contracts\SecurityCheckInterface;
use App\Domain\Security\ValueObjects\SecurityCheckResult;
use Symfony\Component\Finder\Finder;

/**
 * A03: Injection.
 *
 * Scans for potential SQL injection patterns like DB::raw() with concatenation.
 */
class SqlInjectionCheck implements SecurityCheckInterface
{
    /** @var array<string> Patterns that indicate potential SQL injection */
    private const DANGEROUS_PATTERNS = [
        '/DB::raw\s*\(\s*[\'"].*\$/',          // DB::raw() with variable interpolation
        '/whereRaw\s*\(\s*[\'"].*\$/',          // whereRaw() with variable interpolation
        '/selectRaw\s*\(\s*[\'"].*\$/',         // selectRaw() with variable interpolation
        '/orderByRaw\s*\(\s*[\'"].*\$/',        // orderByRaw() with variable interpolation
        '/havingRaw\s*\(\s*[\'"].*\$/',         // havingRaw() with variable interpolation
        '/DB::statement\s*\(\s*[\'"].*\$/',     // DB::statement() with variable interpolation
    ];

    public function getName(): string
    {
        return 'sql_injection';
    }

    public function getCategory(): string
    {
        return 'A03: Injection';
    }

    public function run(): SecurityCheckResult
    {
        $findings = [];
        $recommendations = [];
        $scanDirs = [app_path()];

        foreach ($scanDirs as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            $finder = new Finder();
            $finder->files()->in($dir)->name('*.php');

            foreach ($finder as $file) {
                $content = $file->getContents();
                $relativePath = str_replace(base_path() . '/', '', $file->getRealPath());

                foreach (self::DANGEROUS_PATTERNS as $pattern) {
                    if (preg_match($pattern, $content)) {
                        $findings[] = "Potential SQL injection in {$relativePath}: matches pattern {$pattern}";
                    }
                }
            }
        }

        if (! empty($findings)) {
            $recommendations[] = 'Use parameterized queries with bindings instead of string concatenation';
            $recommendations[] = 'Replace DB::raw($var) with DB::raw("?", [$var]) using bindings';
            $recommendations[] = 'Review all whereRaw/selectRaw/orderByRaw calls for proper parameterization';
        }

        $score = empty($findings) ? 100 : max(0, 100 - (count($findings) * 15));

        return new SecurityCheckResult(
            name: $this->getName(),
            category: $this->getCategory(),
            passed: empty($findings),
            score: $score,
            findings: $findings,
            recommendations: $recommendations,
            severity: empty($findings) ? 'info' : 'critical',
        );
    }
}
