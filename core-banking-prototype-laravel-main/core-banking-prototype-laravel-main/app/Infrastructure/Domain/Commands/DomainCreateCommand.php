<?php

declare(strict_types=1);

namespace App\Infrastructure\Domain\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Scaffold a new domain with standard directory structure.
 */
class DomainCreateCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'domain:create
        {name : The domain name (e.g., Subscription)}
        {--type=optional : Domain type (core or optional)}
        {--description= : Domain description}
        {--with-events : Include Events directory with sample event}
        {--with-workflows : Include Workflows directory structure}
        {--with-tests : Generate test directory structure}
        {--force : Overwrite existing files}';

    /**
     * @var string
     */
    protected $description = 'Create a new domain with standard directory structure';

    private string $domainPath;

    private string $domainName;

    private string $domainNameLower;

    public function handle(): int
    {
        $this->domainName = Str::studly($this->argument('name'));
        $this->domainNameLower = Str::kebab($this->domainName);
        $this->domainPath = app_path("Domain/{$this->domainName}");

        if (File::isDirectory($this->domainPath) && ! $this->option('force')) {
            $this->error("Domain {$this->domainName} already exists. Use --force to overwrite.");

            return self::FAILURE;
        }

        $this->info("Creating domain: {$this->domainName}");
        $this->newLine();

        // Create base directories
        $this->createDirectories();

        // Create module.json
        $this->createModuleManifest();

        // Create base files
        $this->createServiceProvider();
        $this->createBaseException();
        $this->createSampleService();
        $this->createSampleContract();

        // Optional components
        if ($this->option('with-events')) {
            $this->createEventsStructure();
        }

        if ($this->option('with-workflows')) {
            $this->createWorkflowsStructure();
        }

        if ($this->option('with-tests')) {
            $this->createTestsStructure();
        }

        $this->newLine();
        $this->info("<fg=green>✓</> Domain {$this->domainName} created successfully!");
        $this->newLine();
        $this->line('Next steps:');
        $this->line('  1. Register the service provider in bootstrap/providers.php');
        $this->line("  2. Run: php artisan domain:verify {$this->domainNameLower}");
        $this->line('  3. Implement your domain logic in Services/');

        return self::SUCCESS;
    }

    private function createDirectories(): void
    {
        $directories = [
            '',
            '/Contracts',
            '/DataObjects',
            '/Enums',
            '/Exceptions',
            '/Models',
            '/Repositories',
            '/Services',
        ];

        foreach ($directories as $dir) {
            $path = $this->domainPath . $dir;
            if (! File::isDirectory($path)) {
                File::makeDirectory($path, 0755, true);
                $this->line("  <fg=green>Created</> {$path}");
            }
        }
    }

    private function createModuleManifest(): void
    {
        $type = $this->option('type');
        $description = $this->option('description') ?? "The {$this->domainName} domain";

        $manifest = [
            '$schema'      => 'https://finaegis.io/schemas/module.json',
            'name'         => "finaegis/{$this->domainNameLower}",
            'version'      => '1.4.0',
            'description'  => $description,
            'type'         => $type,
            'dependencies' => [
                'required' => [
                    'finaegis/shared' => '^1.0',
                ],
                'optional' => [],
            ],
            'provides' => [
                'interfaces' => [],
                'events'     => [],
                'commands'   => [],
            ],
            'paths' => [
                'migrations' => 'Database/Migrations',
                'tests'      => 'Tests',
            ],
        ];

        $content = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        File::put("{$this->domainPath}/module.json", $content . "\n");
        $this->line('  <fg=green>Created</> module.json');
    }

    private function createServiceProvider(): void
    {
        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Domain\\{$this->domainName}\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the {$this->domainName} domain.
 */
class {$this->domainName}ServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register domain services
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Boot domain services
    }
}

PHP;

        File::makeDirectory("{$this->domainPath}/Providers", 0755, true);
        File::put("{$this->domainPath}/Providers/{$this->domainName}ServiceProvider.php", $content);
        $this->line("  <fg=green>Created</> Providers/{$this->domainName}ServiceProvider.php");
    }

    private function createBaseException(): void
    {
        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Domain\\{$this->domainName}\Exceptions;

use Exception;

/**
 * Base exception for the {$this->domainName} domain.
 */
class {$this->domainName}Exception extends Exception
{
    /**
     * Create a not found exception.
     */
    public static function notFound(string \$identifier): self
    {
        return new self("{$this->domainName} resource not found: {\$identifier}");
    }

    /**
     * Create a validation exception.
     */
    public static function validationFailed(string \$message): self
    {
        return new self("Validation failed: {\$message}");
    }

    /**
     * Create an operation failed exception.
     */
    public static function operationFailed(string \$operation, string \$reason): self
    {
        return new self("{$this->domainName} {\$operation} failed: {\$reason}");
    }
}

PHP;

        File::put("{$this->domainPath}/Exceptions/{$this->domainName}Exception.php", $content);
        $this->line("  <fg=green>Created</> Exceptions/{$this->domainName}Exception.php");
    }

    private function createSampleService(): void
    {
        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Domain\\{$this->domainName}\Services;

use App\Domain\\{$this->domainName}\Contracts\\{$this->domainName}ServiceInterface;

/**
 * Main service for the {$this->domainName} domain.
 */
class {$this->domainName}Service implements {$this->domainName}ServiceInterface
{
    /**
     * {@inheritDoc}
     */
    public function getStatus(): array
    {
        return [
            'domain'  => '{$this->domainNameLower}',
            'status'  => 'operational',
            'version' => '1.4.0',
        ];
    }
}

PHP;

        File::put("{$this->domainPath}/Services/{$this->domainName}Service.php", $content);
        $this->line("  <fg=green>Created</> Services/{$this->domainName}Service.php");
    }

    private function createSampleContract(): void
    {
        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Domain\\{$this->domainName}\Contracts;

/**
 * Contract for the main {$this->domainName} service.
 */
interface {$this->domainName}ServiceInterface
{
    /**
     * Get the domain service status.
     *
     * @return array{domain: string, status: string, version: string}
     */
    public function getStatus(): array;
}

PHP;

        File::put("{$this->domainPath}/Contracts/{$this->domainName}ServiceInterface.php", $content);
        $this->line("  <fg=green>Created</> Contracts/{$this->domainName}ServiceInterface.php");
    }

    private function createEventsStructure(): void
    {
        File::makeDirectory("{$this->domainPath}/Events", 0755, true);

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Domain\\{$this->domainName}\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

/**
 * Base event for the {$this->domainName} domain.
 */
abstract class {$this->domainName}Event extends ShouldBeStored
{
    public function __construct(
        public readonly string \$aggregateId,
        public readonly array \$metadata = [],
    ) {
    }
}

PHP;

        File::put("{$this->domainPath}/Events/{$this->domainName}Event.php", $content);
        $this->line("  <fg=green>Created</> Events/{$this->domainName}Event.php");
    }

    private function createWorkflowsStructure(): void
    {
        $directories = [
            '/Workflows',
            '/Workflows/Activities',
        ];

        foreach ($directories as $dir) {
            File::makeDirectory($this->domainPath . $dir, 0755, true);
        }

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Domain\\{$this->domainName}\Workflows\Activities;

use Workflow\Activity;

/**
 * Base activity for {$this->domainName} workflows.
 */
abstract class {$this->domainName}Activity extends Activity
{
    /**
     * Get the activity name for logging.
     */
    abstract protected function getActivityName(): string;
}

PHP;

        File::put("{$this->domainPath}/Workflows/Activities/{$this->domainName}Activity.php", $content);
        $this->line("  <fg=green>Created</> Workflows/Activities/{$this->domainName}Activity.php");
    }

    private function createTestsStructure(): void
    {
        $testPath = base_path("tests/Domain/{$this->domainName}");

        $directories = [
            '',
            '/Services',
            '/Models',
        ];

        foreach ($directories as $dir) {
            $path = $testPath . $dir;
            if (! File::isDirectory($path)) {
                File::makeDirectory($path, 0755, true);
            }
        }

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace Tests\Domain\\{$this->domainName}\Services;

use App\Domain\\{$this->domainName}\Services\\{$this->domainName}Service;
use Tests\TestCase;

class {$this->domainName}ServiceTest extends TestCase
{
    private {$this->domainName}Service \$service;

    protected function setUp(): void
    {
        parent::setUp();
        \$this->service = new {$this->domainName}Service();
    }

    public function test_get_status_returns_operational(): void
    {
        \$status = \$this->service->getStatus();

        \$this->assertIsArray(\$status);
        \$this->assertEquals('{$this->domainNameLower}', \$status['domain']);
        \$this->assertEquals('operational', \$status['status']);
    }
}

PHP;

        File::put("{$testPath}/Services/{$this->domainName}ServiceTest.php", $content);
        $this->line("  <fg=green>Created</> tests/Domain/{$this->domainName}/Services/{$this->domainName}ServiceTest.php");
    }
}
