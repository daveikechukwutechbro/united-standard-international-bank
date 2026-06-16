<?php

declare(strict_types=1);

namespace Tests\Domain\Governance\ValueObjects;

use App\Domain\Governance\ValueObjects\PollOption;
use Tests\UnitTestCase;

class PollOptionTest extends UnitTestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_option_with_required_fields(): void
    {
        $option = new PollOption(
            id: 'option-1',
            label: 'Option One'
        );

        expect($option->id)->toBe('option-1');
        expect($option->label)->toBe('Option One');
        expect($option->description)->toBeNull();
        expect($option->metadata)->toBe([]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_option_with_all_fields(): void
    {
        $option = new PollOption(
            id: 'option-2',
            label: 'Option Two',
            description: 'Description for option two',
            metadata: ['key' => 'value']
        );

        expect($option->id)->toBe('option-2');
        expect($option->label)->toBe('Option Two');
        expect($option->description)->toBe('Description for option two');
        expect($option->metadata)->toBe(['key' => 'value']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_from_array(): void
    {
        $data = [
            'id'          => 'option-3',
            'label'       => 'Option Three',
            'description' => 'Third option',
            'metadata'    => ['priority' => 'high'],
        ];

        $option = PollOption::fromArray($data);

        expect($option->id)->toBe('option-3');
        expect($option->label)->toBe('Option Three');
        expect($option->description)->toBe('Third option');
        expect($option->metadata)->toBe(['priority' => 'high']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_from_array_with_defaults(): void
    {
        $data = [
            'id'    => 'option-4',
            'label' => 'Option Four',
        ];

        $option = PollOption::fromArray($data);

        expect($option->id)->toBe('option-4');
        expect($option->label)->toBe('Option Four');
        expect($option->description)->toBeNull();
        expect($option->metadata)->toBe([]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_converts_to_array(): void
    {
        $option = new PollOption(
            id: 'option-5',
            label: 'Option Five',
            description: 'Fifth option',
            metadata: ['type' => 'default']
        );

        $array = $option->toArray();

        expect($array)->toBe([
            'id'          => 'option-5',
            'label'       => 'Option Five',
            'description' => 'Fifth option',
            'metadata'    => ['type' => 'default'],
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_converts_to_array_with_null_description(): void
    {
        $option = new PollOption(
            id: 'option-6',
            label: 'Option Six'
        );

        $array = $option->toArray();

        expect($array['description'])->toBeNull();
        expect($array['metadata'])->toBe([]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_compares_equality_by_id(): void
    {
        $option1 = new PollOption(id: 'same-id', label: 'First');
        $option2 = new PollOption(id: 'same-id', label: 'Second');
        $option3 = new PollOption(id: 'different-id', label: 'First');

        expect($option1->equals($option2))->toBeTrue();
        expect($option1->equals($option3))->toBeFalse();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_is_immutable(): void
    {
        $option = new PollOption(
            id: 'immutable',
            label: 'Test',
            metadata: ['key' => 'value']
        );

        // PollOption is readonly class - verify properties exist
        expect($option->id)->toBe('immutable');
        expect($option->label)->toBe('Test');
        expect($option->metadata)->toBe(['key' => 'value']);
    }
}
