<?php

declare(strict_types=1);

namespace App\Domain\Security\Checks;

use App\Domain\Security\Contracts\SecurityCheckInterface;
use App\Domain\Security\ValueObjects\SecurityCheckResult;
use Symfony\Component\Finder\Finder;

/**
 * A03: Injection.
 *
 * Scans controllers for proper FormRequest usage and input validation.
 */
class InputValidationCheck implements SecurityCheckInterface
{
    public function getName(): string
    {
        return 'input_validation';
    }

    public function getCategory(): string
    {
        return 'A03: Injection';
    }

    public function run(): SecurityCheckResult
    {
        $findings = [];
        $recommendations = [];
        $controllersDir = app_path('Http/Controllers');

        if (! is_dir($controllersDir)) {
            return new SecurityCheckResult(
                name: $this->getName(),
                category: $this->getCategory(),
                passed: true,
                score: 100,
                findings: [],
                recommendations: [],
                severity: 'info',
            );
        }

        $finder = new Finder();
        $finder->files()->in($controllersDir)->name('*Controller.php');

        $totalControllers = 0;
        $controllersWithValidation = 0;

        foreach ($finder as $file) {
            $content = $file->getContents();
            $relativePath = str_replace(base_path() . '/', '', $file->getRealPath());
            $totalControllers++;

            // Check for FormRequest type hints or $request->validate()
            $hasValidation = str_contains($content, 'FormRequest')
                || str_contains($content, '->validate(')
                || str_contains($content, '->validated(')
                || str_contains($content, 'Validator::make');

            // Check for direct request input without validation
            $hasDirectInput = (bool) preg_match('/\$request->(?:input|get|post|query)\s*\(/', $content);

            if ($hasValidation) {
                $controllersWithValidation++;
            } elseif ($hasDirectInput) {
                $findings[] = "Controller uses unvalidated input: {$relativePath}";
            }
        }

        if (! empty($findings)) {
            $recommendations[] = 'Use FormRequest classes for input validation in controllers';
            $recommendations[] = 'Always validate and sanitize user input before processing';
        }

        $score = $totalControllers > 0
            ? (int) round(($controllersWithValidation / $totalControllers) * 100)
            : 100;

        return new SecurityCheckResult(
            name: $this->getName(),
            category: $this->getCategory(),
            passed: empty($findings),
            score: $score,
            findings: $findings,
            recommendations: $recommendations,
            severity: empty($findings) ? 'info' : 'medium',
        );
    }
}
