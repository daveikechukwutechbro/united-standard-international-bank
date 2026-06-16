<?php

declare(strict_types=1);

namespace Tests\Domain\Governance\Enums;

use App\Domain\Governance\Enums\PollType;
use Tests\UnitTestCase;

class PollTypeTest extends UnitTestCase
{
    // ===========================================
    // Type Values Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_all_expected_type_values(): void
    {
        $types = PollType::cases();

        expect($types)->toHaveCount(5);
        expect(array_column($types, 'value'))->toBe([
            'single_choice',
            'multiple_choice',
            'weighted_choice',
            'yes_no',
            'ranked_choice',
        ]);
    }

    // ===========================================
    // getLabel Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_correct_labels(): void
    {
        expect(PollType::SINGLE_CHOICE->getLabel())->toBe('Single Choice');
        expect(PollType::MULTIPLE_CHOICE->getLabel())->toBe('Multiple Choice');
        expect(PollType::WEIGHTED_CHOICE->getLabel())->toBe('Weighted Choice');
        expect(PollType::YES_NO->getLabel())->toBe('Yes/No');
        expect(PollType::RANKED_CHOICE->getLabel())->toBe('Ranked Choice');
    }

    // ===========================================
    // getDescription Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_correct_descriptions(): void
    {
        expect(PollType::SINGLE_CHOICE->getDescription())
            ->toBe('Voters can select only one option');

        expect(PollType::MULTIPLE_CHOICE->getDescription())
            ->toBe('Voters can select multiple options');

        expect(PollType::WEIGHTED_CHOICE->getDescription())
            ->toBe('Voters can allocate weight/percentage to options');

        expect(PollType::YES_NO->getDescription())
            ->toBe('Simple yes or no question');

        expect(PollType::RANKED_CHOICE->getDescription())
            ->toBe('Voters rank options in order of preference');
    }

    // ===========================================
    // allowsMultipleSelections Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_multiple_selections_for_multi_choice_types(): void
    {
        expect(PollType::MULTIPLE_CHOICE->allowsMultipleSelections())->toBeTrue();
        expect(PollType::WEIGHTED_CHOICE->allowsMultipleSelections())->toBeTrue();
        expect(PollType::RANKED_CHOICE->allowsMultipleSelections())->toBeTrue();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_disallows_multiple_selections_for_single_choice_types(): void
    {
        expect(PollType::SINGLE_CHOICE->allowsMultipleSelections())->toBeFalse();
        expect(PollType::YES_NO->allowsMultipleSelections())->toBeFalse();
    }

    // ===========================================
    // Value Casting Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_from_string_value(): void
    {
        expect(PollType::from('single_choice'))->toBe(PollType::SINGLE_CHOICE);
        expect(PollType::from('multiple_choice'))->toBe(PollType::MULTIPLE_CHOICE);
        expect(PollType::from('yes_no'))->toBe(PollType::YES_NO);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_null_for_invalid_value(): void
    {
        expect(PollType::tryFrom('invalid'))->toBeNull();
        expect(PollType::tryFrom(''))->toBeNull();
        expect(PollType::tryFrom('singlechoice'))->toBeNull();
    }

    // ===========================================
    // Type Categorization Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_categorizes_simple_voting_types(): void
    {
        $simpleTypes = [
            PollType::SINGLE_CHOICE,
            PollType::YES_NO,
        ];

        foreach ($simpleTypes as $type) {
            expect($type->allowsMultipleSelections())->toBeFalse();
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_categorizes_complex_voting_types(): void
    {
        $complexTypes = [
            PollType::MULTIPLE_CHOICE,
            PollType::WEIGHTED_CHOICE,
            PollType::RANKED_CHOICE,
        ];

        foreach ($complexTypes as $type) {
            expect($type->allowsMultipleSelections())->toBeTrue();
        }
    }
}
