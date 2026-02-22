<?php

use OsamaDev\FilamentCalculatorAction\CalcField;
use OsamaDev\FilamentCalculatorAction\Concerns\HasCalculation;

// Helper: create a trait-using object with given calcFields
function makeCalculator(array $fields, ?string $calcPrefix = null): object
{
    $obj = new class {
        use HasCalculation;
    };

    $obj->calcFields($fields);

    if ($calcPrefix !== null) {
        $obj->calcPrefix($calcPrefix);
    }

    return $obj;
}

// ─── computeResult() ────────────────────────────────────────────────────────

test('computeResult() adds fields correctly', function () {
    $calc = makeCalculator([
        CalcField::make('price')->adds(),
        CalcField::make('tax')->adds(),
        CalcField::make('total')->result(),
    ]);

    expect($calc->computeResult(['price' => 100, 'tax' => 15]))->toBe(115.0);
});

test('computeResult() subtracts fields correctly', function () {
    $calc = makeCalculator([
        CalcField::make('subtotal')->adds(),
        CalcField::make('discount')->subtracts(),
        CalcField::make('total')->result(),
    ]);

    expect($calc->computeResult(['subtotal' => 100, 'discount' => 20]))->toBe(80.0);
});

test('computeResult() handles mixed adds and subtracts', function () {
    $calc = makeCalculator([
        CalcField::make('subtotal')->adds(),
        CalcField::make('extra_fees')->adds(),
        CalcField::make('discount')->subtracts(),
        CalcField::make('total')->result(),
    ]);

    expect($calc->computeResult(['subtotal' => 100, 'extra_fees' => 20, 'discount' => 10]))->toBe(110.0);
});

test('computeResult() never returns negative', function () {
    $calc = makeCalculator([
        CalcField::make('price')->adds(),
        CalcField::make('discount')->subtracts(),
        CalcField::make('total')->result(),
    ]);

    expect($calc->computeResult(['price' => 10, 'discount' => 999]))->toBe(0.0);
});

test('computeResult() with zero values', function () {
    $calc = makeCalculator([
        CalcField::make('price')->adds(),
        CalcField::make('discount')->subtracts(),
        CalcField::make('total')->result(),
    ]);

    expect($calc->computeResult(['price' => 0, 'discount' => 0]))->toBe(0.0);
});

// ─── buildAlpineData() ──────────────────────────────────────────────────────

test('buildAlpineData() contains all non-result field names as properties', function () {
    $calc = makeCalculator([
        CalcField::make('subtotal')->adds(),
        CalcField::make('discount')->subtracts(),
        CalcField::make('total')->result(),
    ]);

    $alpine = $calc->buildAlpineData();

    expect($alpine)->toContain('subtotal')
        ->toContain('discount')
        ->not->toMatch('/\btotal\s*:/');
});

test('buildAlpineData() contains get result() getter', function () {
    $calc = makeCalculator([
        CalcField::make('price')->adds(),
        CalcField::make('total')->result(),
    ]);

    expect($calc->buildAlpineData())->toContain('get result()');
});

test('buildAlpineData() expression uses addition for add role fields', function () {
    $calc = makeCalculator([
        CalcField::make('subtotal')->adds(),
        CalcField::make('total')->result(),
    ]);

    $alpine = $calc->buildAlpineData();

    expect($alpine)->toContain('this.subtotal');

    // The add expression should not use subtraction for subtotal
    expect($alpine)->not->toMatch('/- parseFloat\(this\.subtotal/');
});

test('buildAlpineData() expression uses subtraction for subtract role fields', function () {
    $calc = makeCalculator([
        CalcField::make('subtotal')->adds(),
        CalcField::make('discount')->subtracts(),
        CalcField::make('total')->result(),
    ]);

    $alpine = $calc->buildAlpineData();

    expect($alpine)->toContain('- parseFloat(this.discount');
});

test('buildAlpineData() includes Math.max(0, ...) for floor protection', function () {
    $calc = makeCalculator([
        CalcField::make('price')->adds(),
        CalcField::make('total')->result(),
    ]);

    expect($calc->buildAlpineData())->toContain('Math.max(0,');
});

// ─── CalcField fluent API ────────────────────────────────────────────────────

test('CalcField fluent chain works correctly', function () {
    $field = CalcField::make('price')
        ->adds()
        ->label('Price')
        ->prefix('EGP')
        ->required()
        ->default(50);

    expect($field->getName())->toBe('price');
    expect($field->getRole())->toBe('add');
    expect($field->getLabel())->toBe('Price');
    expect($field->getPrefix())->toBe('EGP');
    expect($field->isRequired())->toBeTrue();
    expect($field->getDefault())->toBe(50.0);
});

test('CalcField result() sets role correctly', function () {
    $field = CalcField::make('total')->result();

    expect($field->isResult())->toBeTrue();
    expect($field->getRole())->toBe('result');
});

test('CalcField default() accepts Closure', function () {
    $field = CalcField::make('price')->default(fn () => 99.0);

    $closure = $field->getDefault();
    expect($closure)->toBeInstanceOf(Closure::class);
    expect($closure())->toBe(99.0);
});

// ─── buildCalcInputs() ───────────────────────────────────────────────────────

test('buildCalcInputs() returns correct count', function () {
    $calc = makeCalculator([
        CalcField::make('a')->adds(),
        CalcField::make('b')->adds(),
        CalcField::make('c')->subtracts(),
        CalcField::make('total')->result(),
    ]);

    expect($calc->buildCalcInputs())->toHaveCount(4);
});

test('Result field has x-bind:value=result attribute', function () {
    $calc = makeCalculator([
        CalcField::make('price')->adds(),
        CalcField::make('total')->result(),
    ]);

    $inputs = $calc->buildCalcInputs();

    // The last input should be the result field
    $resultInput = collect($inputs)->first(fn ($input) => $input->getName() === 'total');

    expect($resultInput)->not->toBeNull();

    $attrs = $resultInput->getExtraInputAttributes();
    expect($attrs)->toHaveKey('x-bind:value', 'result');
});

test('Non-result fields have x-model.number attribute', function () {
    $calc = makeCalculator([
        CalcField::make('price')->adds(),
        CalcField::make('total')->result(),
    ]);

    $inputs = $calc->buildCalcInputs();

    $priceInput = collect($inputs)->first(fn ($input) => $input->getName() === 'price');

    expect($priceInput)->not->toBeNull();

    $attrs = $priceInput->getExtraInputAttributes();
    expect($attrs)->toHaveKey('x-model.number', 'price');
});

test('calcPrefix() is used as fallback when CalcField has no prefix', function () {
    $calc = makeCalculator([
        CalcField::make('price')->adds(),
        CalcField::make('total')->result(),
    ], 'USD');

    $inputs = $calc->buildCalcInputs();

    $priceInput = collect($inputs)->first(fn ($input) => $input->getName() === 'price');

    expect($priceInput)->not->toBeNull();
    expect($priceInput->getPrefix())->toBe('USD');
});

test('CalcField prefix() overrides calcPrefix()', function () {
    $calc = makeCalculator([
        CalcField::make('price')->adds()->prefix('EGP'),
        CalcField::make('total')->result(),
    ], 'USD');

    $inputs = $calc->buildCalcInputs();

    $priceInput = collect($inputs)->first(fn ($input) => $input->getName() === 'price');

    expect($priceInput)->not->toBeNull();
    expect($priceInput->getPrefix())->toBe('EGP');
});
