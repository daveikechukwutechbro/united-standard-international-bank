<?php

declare(strict_types=1);

namespace Tests\Domain\AI\MCP;

use App\Domain\AI\Contracts\MCPToolInterface;
use App\Domain\AI\Exceptions\ToolAlreadyRegisteredException;
use App\Domain\AI\Exceptions\ToolNotFoundException;
use App\Domain\AI\MCP\ToolRegistry;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ToolRegistry.
 */
class ToolRegistryTest extends TestCase
{
    private ToolRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new ToolRegistry();

        // Mock the Log facade to prevent actual logging
        Log::shouldReceive('info')->andReturnNull();
        Log::shouldReceive('debug')->andReturnNull();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a mock tool with specified properties.
     */
    private function createMockTool(
        string $name,
        string $category = 'general',
        string $description = 'Test tool',
        array $capabilities = [],
        bool $cacheable = false,
        int $cacheTtl = 0
    ): MCPToolInterface&MockInterface {
        $tool = Mockery::mock(MCPToolInterface::class);
        $tool->shouldReceive('getName')->andReturn($name);
        $tool->shouldReceive('getCategory')->andReturn($category);
        $tool->shouldReceive('getDescription')->andReturn($description);
        $tool->shouldReceive('getCapabilities')->andReturn($capabilities);
        $tool->shouldReceive('isCacheable')->andReturn($cacheable);
        $tool->shouldReceive('getCacheTtl')->andReturn($cacheTtl);
        $tool->shouldReceive('getInputSchema')->andReturn([]);
        $tool->shouldReceive('getOutputSchema')->andReturn([]);

        return $tool;
    }

    // Registration tests

    public function test_register_adds_tool_to_registry(): void
    {
        $tool = $this->createMockTool('test-tool');

        $this->registry->register($tool);

        $this->assertTrue($this->registry->has('test-tool'));
    }

    public function test_register_throws_exception_for_duplicate_tool(): void
    {
        $tool1 = $this->createMockTool('duplicate-tool');
        $tool2 = $this->createMockTool('duplicate-tool');

        $this->registry->register($tool1);

        $this->expectException(ToolAlreadyRegisteredException::class);
        $this->expectExceptionMessage('Tool already registered: duplicate-tool');

        $this->registry->register($tool2);
    }

    public function test_register_organizes_by_category(): void
    {
        $tool1 = $this->createMockTool('tool1', 'banking');
        $tool2 = $this->createMockTool('tool2', 'banking');
        $tool3 = $this->createMockTool('tool3', 'trading');

        $this->registry->register($tool1);
        $this->registry->register($tool2);
        $this->registry->register($tool3);

        $bankingTools = $this->registry->getToolsByCategory('banking');
        $tradingTools = $this->registry->getToolsByCategory('trading');

        $this->assertCount(2, $bankingTools);
        $this->assertCount(1, $tradingTools);
    }

    // Unregistration tests

    public function test_unregister_removes_tool(): void
    {
        $tool = $this->createMockTool('removable-tool');

        $this->registry->register($tool);
        $this->assertTrue($this->registry->has('removable-tool'));

        $this->registry->unregister('removable-tool');
        $this->assertFalse($this->registry->has('removable-tool'));
    }

    public function test_unregister_throws_exception_for_nonexistent_tool(): void
    {
        $this->expectException(ToolNotFoundException::class);
        $this->expectExceptionMessage('Tool not found: nonexistent');

        $this->registry->unregister('nonexistent');
    }

    // Get tests

    public function test_get_returns_registered_tool(): void
    {
        $tool = $this->createMockTool('fetch-tool');

        $this->registry->register($tool);

        $retrieved = $this->registry->get('fetch-tool');

        $this->assertSame($tool, $retrieved);
    }

    public function test_get_throws_exception_for_nonexistent_tool(): void
    {
        $this->expectException(ToolNotFoundException::class);
        $this->expectExceptionMessage('Tool not found: nonexistent');

        $this->registry->get('nonexistent');
    }

    // Has tests

    public function test_has_returns_true_for_registered_tool(): void
    {
        $tool = $this->createMockTool('exists-tool');

        $this->registry->register($tool);

        $this->assertTrue($this->registry->has('exists-tool'));
    }

    public function test_has_returns_false_for_unregistered_tool(): void
    {
        $this->assertFalse($this->registry->has('not-registered'));
    }

    // GetAllTools tests

    public function test_get_all_tools_returns_empty_array_initially(): void
    {
        $tools = $this->registry->getAllTools();

        $this->assertIsArray($tools);
        $this->assertEmpty($tools);
    }

    public function test_get_all_tools_returns_all_registered_tools(): void
    {
        $tool1 = $this->createMockTool('tool-a');
        $tool2 = $this->createMockTool('tool-b');
        $tool3 = $this->createMockTool('tool-c');

        $this->registry->register($tool1);
        $this->registry->register($tool2);
        $this->registry->register($tool3);

        $tools = $this->registry->getAllTools();

        $this->assertCount(3, $tools);
        $this->assertArrayHasKey('tool-a', $tools);
        $this->assertArrayHasKey('tool-b', $tools);
        $this->assertArrayHasKey('tool-c', $tools);
    }

    // GetToolsByCategory tests

    public function test_get_tools_by_category_returns_empty_collection_for_unknown_category(): void
    {
        $tools = $this->registry->getToolsByCategory('unknown');

        $this->assertCount(0, $tools);
    }

    public function test_get_tools_by_category_returns_matching_tools(): void
    {
        $tool1 = $this->createMockTool('banking-1', 'banking');
        $tool2 = $this->createMockTool('banking-2', 'banking');

        $this->registry->register($tool1);
        $this->registry->register($tool2);

        $tools = $this->registry->getToolsByCategory('banking');

        $this->assertCount(2, $tools);
    }

    // GetCategories tests

    public function test_get_categories_returns_empty_initially(): void
    {
        $categories = $this->registry->getCategories();

        $this->assertIsArray($categories);
        $this->assertEmpty($categories);
    }

    public function test_get_categories_returns_unique_categories(): void
    {
        $tool1 = $this->createMockTool('tool1', 'category-a');
        $tool2 = $this->createMockTool('tool2', 'category-b');
        $tool3 = $this->createMockTool('tool3', 'category-a');

        $this->registry->register($tool1);
        $this->registry->register($tool2);
        $this->registry->register($tool3);

        $categories = $this->registry->getCategories();

        $this->assertCount(2, $categories);
        $this->assertContains('category-a', $categories);
        $this->assertContains('category-b', $categories);
    }

    // SearchTools tests

    public function test_search_tools_finds_by_name(): void
    {
        $tool = $this->createMockTool('account-balance', 'banking', 'Get account balance');

        $this->registry->register($tool);

        $results = $this->registry->searchTools('account');

        $this->assertCount(1, $results);
    }

    public function test_search_tools_finds_by_description(): void
    {
        $tool = $this->createMockTool('balance-tool', 'banking', 'Retrieves current account balance');

        $this->registry->register($tool);

        $results = $this->registry->searchTools('current');

        $this->assertCount(1, $results);
    }

    public function test_search_tools_finds_by_category(): void
    {
        $tool = $this->createMockTool('some-tool', 'cryptocurrency', 'Some description');

        $this->registry->register($tool);

        $results = $this->registry->searchTools('crypto');

        $this->assertCount(1, $results);
    }

    public function test_search_tools_is_case_insensitive(): void
    {
        $tool = $this->createMockTool('AccountBalance', 'Banking', 'Get Balance');

        $this->registry->register($tool);

        $results = $this->registry->searchTools('ACCOUNTBALANCE');

        $this->assertCount(1, $results);
    }

    public function test_search_tools_returns_empty_for_no_match(): void
    {
        $tool = $this->createMockTool('tool', 'category', 'description');

        $this->registry->register($tool);

        $results = $this->registry->searchTools('nonexistent');

        $this->assertCount(0, $results);
    }

    // GetToolsWithCapability tests

    public function test_get_tools_with_capability_returns_matching_tools(): void
    {
        $tool1 = $this->createMockTool('tool1', 'cat', 'desc', ['read', 'write']);
        $tool2 = $this->createMockTool('tool2', 'cat', 'desc', ['read']);
        $tool3 = $this->createMockTool('tool3', 'cat', 'desc', ['admin']);

        $this->registry->register($tool1);
        $this->registry->register($tool2);
        $this->registry->register($tool3);

        $readTools = $this->registry->getToolsWithCapability('read');
        $adminTools = $this->registry->getToolsWithCapability('admin');

        $this->assertCount(2, $readTools);
        $this->assertCount(1, $adminTools);
    }

    public function test_get_tools_with_capability_returns_empty_for_no_match(): void
    {
        $tool = $this->createMockTool('tool', 'cat', 'desc', ['basic']);

        $this->registry->register($tool);

        $results = $this->registry->getToolsWithCapability('advanced');

        $this->assertCount(0, $results);
    }

    // ExportSchema tests

    public function test_export_schema_returns_correct_structure(): void
    {
        $schema = $this->registry->exportSchema();

        $this->assertArrayHasKey('version', $schema);
        $this->assertArrayHasKey('tools', $schema);
        $this->assertArrayHasKey('categories', $schema);
        $this->assertEquals('1.0', $schema['version']);
    }

    public function test_export_schema_includes_tool_details(): void
    {
        $tool = $this->createMockTool('schema-tool', 'test-cat', 'Test description', ['cap1'], true, 300);

        $this->registry->register($tool);

        $schema = $this->registry->exportSchema();

        $this->assertCount(1, $schema['tools']);
        $toolSchema = $schema['tools'][0];

        $this->assertEquals('schema-tool', $toolSchema['name']);
        $this->assertEquals('test-cat', $toolSchema['category']);
        $this->assertEquals('Test description', $toolSchema['description']);
        $this->assertEquals(['cap1'], $toolSchema['capabilities']);
        $this->assertTrue($toolSchema['cacheable']);
        $this->assertEquals(300, $toolSchema['cacheTtl']);
    }

    public function test_export_schema_includes_category_details(): void
    {
        $tool1 = $this->createMockTool('tool1', 'cat-x');
        $tool2 = $this->createMockTool('tool2', 'cat-x');

        $this->registry->register($tool1);
        $this->registry->register($tool2);

        $schema = $this->registry->exportSchema();

        $this->assertCount(1, $schema['categories']);
        $categorySchema = $schema['categories'][0];

        $this->assertEquals('cat-x', $categorySchema['name']);
        $this->assertEquals(2, $categorySchema['toolCount']);
        $this->assertContains('tool1', $categorySchema['tools']);
        $this->assertContains('tool2', $categorySchema['tools']);
    }

    // GetStatistics tests

    public function test_get_statistics_returns_correct_structure(): void
    {
        $stats = $this->registry->getStatistics();

        $this->assertArrayHasKey('total_tools', $stats);
        $this->assertArrayHasKey('categories', $stats);
        $this->assertArrayHasKey('tools_by_category', $stats);
        $this->assertArrayHasKey('cacheable_tools', $stats);
    }

    public function test_get_statistics_counts_tools_correctly(): void
    {
        $tool1 = $this->createMockTool('tool1', 'cat-a', 'desc', [], true);
        $tool2 = $this->createMockTool('tool2', 'cat-a', 'desc', [], false);
        $tool3 = $this->createMockTool('tool3', 'cat-b', 'desc', [], true);

        $this->registry->register($tool1);
        $this->registry->register($tool2);
        $this->registry->register($tool3);

        $stats = $this->registry->getStatistics();

        $this->assertEquals(3, $stats['total_tools']);
        $this->assertEquals(2, $stats['categories']);
        $this->assertEquals(2, $stats['cacheable_tools']);
        $this->assertEquals(['cat-a' => 2, 'cat-b' => 1], $stats['tools_by_category']);
    }

    public function test_get_statistics_returns_zeros_for_empty_registry(): void
    {
        $stats = $this->registry->getStatistics();

        $this->assertEquals(0, $stats['total_tools']);
        $this->assertEquals(0, $stats['categories']);
        $this->assertEquals(0, $stats['cacheable_tools']);
        $this->assertEquals([], $stats['tools_by_category']);
    }
}
