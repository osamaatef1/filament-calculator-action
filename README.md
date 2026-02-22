# filament-calculator-action

[![Latest Version on Packagist](https://img.shields.io/packagist/v/osama-dev/filament-calculator-action.svg?style=flat-square)](https://packagist.org/packages/osama-dev/filament-calculator-action)
[![License](https://img.shields.io/packagist/l/osama-dev/filament-calculator-action.svg?style=flat-square)](LICENSE.md)

A plug-and-play real-time calculator action for **Filament v3** — instant client-side arithmetic with zero Livewire round-trips.

Works in both **table row actions** (`CalculatorAction`) and **page header actions** (`CalculatorPageAction`).

---

## Why this package?

Using `->live()` on Filament form fields triggers a Livewire server round-trip on every keystroke. For a simple numeric calculator (subtotal + fees − discount = total), that's a server call every time the user types a digit — sluggish and unnecessary.

This package generates lightweight inline JavaScript (`onkeyup` / `onchange`) that performs all arithmetic directly in the browser. No server call. No debounce. No waiting. The result field updates the instant a key is released.

Server-side recomputation via `computeResult()` is still performed inside `->action()` to ensure the stored value is always trustworthy.

---

## Installation

```bash
composer require osama-dev/filament-calculator-action
```

No extra configuration needed — the service provider is auto-discovered.

---

## Two action classes

| Class | Extends | Use in |
|---|---|---|
| `CalculatorAction` | `Filament\Tables\Actions\Action` | Table row actions |
| `CalculatorPageAction` | `Filament\Actions\Action` | Page header actions (`getHeaderActions()`) |

Both share the exact same API via the `HasCalculation` trait.

---

## Basic Usage — Table Action

```php
use OsamaDev\FilamentCalculatorAction\CalculatorAction;
use OsamaDev\FilamentCalculatorAction\CalcField;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

CalculatorAction::make('issue_receipt')
    ->label('Mark Fulfilled & Issue Receipt')
    ->icon('heroicon-o-document-text')
    ->color('success')
    ->visible(fn ($record): bool => $record->status === 'in_progress')
    ->calcSectionHeading('Receipt Details')
    ->calcColumns(2)
    ->calcPrefix('EGP')
    ->calcFields([
        CalcField::make('subtotal')
            ->label('Subtotal')
            ->adds()
            ->required()
            ->helperText('including taxes')
            ->default(fn ($record) => (float) ($record->sub_total ?? 0))
            ->columnSpan(1),
        CalcField::make('extra_fees')
            ->label('Extra Fees')
            ->adds()
            ->default(0)
            ->columnSpan(1),
        CalcField::make('discount')
            ->label('Discount')
            ->subtracts()
            ->default(0)
            ->columnSpan(1),
        CalcField::make('total')
            ->label('Total')
            ->result(),
    ])
    ->form([
        Section::make('Booking Context')
            ->schema([
                TextInput::make('user_name')
                    ->label('User')
                    ->default(fn ($record) => $record->user?->full_name ?? 'Deleted User')
                    ->disabled(),
                TextInput::make('reference')
                    ->label('Reference')
                    ->default(fn ($record) => $record->reference)
                    ->disabled(),
            ])->columns(2),
        Textarea::make('notes')
            ->label('Notes')
            ->rows(3)
            ->columnSpanFull(),
    ])
    ->action(function (array $data, $record) {
        $subtotal = (float) ($data['subtotal'] ?? 0);
        $discount = (float) ($data['discount'] ?? 0);
        $extraFees = (float) ($data['extra_fees'] ?? 0);
        $total = $subtotal - $discount + $extraFees;

        // Or use the helper: $total = $this->computeResult($data);

        $record->invoice()->create([
            'subtotal'   => $subtotal,
            'discount'   => $discount,
            'extra_fees' => $extraFees,
            'total'      => $total,
            'notes'      => $data['notes'] ?? null,
        ]);
    })
    ->modalHeading('Issue Receipt')
    ->modalSubmitActionLabel('Create Receipt')
```

> **Note:** `->form([...])` defines your custom fields. The calculator section is always appended **after** your form fields automatically.

---

## Page Header Action

Use `CalculatorPageAction` when placing the action in `getHeaderActions()` on a resource page (View, Edit, etc.):

```php
use OsamaDev\FilamentCalculatorAction\CalculatorPageAction;
use OsamaDev\FilamentCalculatorAction\CalcField;

protected function getHeaderActions(): array
{
    return [
        CalculatorPageAction::make('issue_receipt')
            ->label('Mark Fulfilled & Issue Receipt')
            ->icon('heroicon-o-document-text')
            ->color('success')
            ->visible(fn ($record): bool => $record->status === 'in_progress')
            ->calcSectionHeading('Receipt Details')
            ->calcPrefix('EGP')
            ->calcColumns(2)
            ->calcFields([
                CalcField::make('subtotal')->label('Subtotal')->adds()->required()->default(fn ($record) => (float) ($record->sub_total ?? 0))->columnSpan(1),
                CalcField::make('extra_fees')->label('Extra Fees')->adds()->default(0)->columnSpan(1),
                CalcField::make('discount')->label('Discount')->subtracts()->default(0)->columnSpan(1),
                CalcField::make('total')->label('Total')->result(),
            ])
            ->action(function (array $data, $record) {
                $total = $this->computeResult($data);
                // persist invoice...
            }),
    ];
}
```

---

## Multiply & Divide Example — Quote Builder

```php
CalculatorAction::make('generate_quote')
    ->label('Generate Quote')
    ->calcSectionHeading('Quote Breakdown')
    ->calcColumns(2)
    ->calcPrefix('EGP')
    ->calcFields([
        CalcField::make('unit_price')
            ->label('Unit Price')
            ->adds()
            ->required()
            ->columnSpan(1),
        CalcField::make('quantity')
            ->label('Quantity')
            ->multiplies()
            ->default(1)
            ->columnSpan(1),
        CalcField::make('discount')
            ->label('Discount')
            ->subtracts()
            ->default(0)
            ->columnSpan(1),
        CalcField::make('total')
            ->label('Total')
            ->result(),
    ])
    ->action(function (array $data, $record) {
        $total = $this->computeResult($data);
        // formula: (unit_price - discount) * quantity
    })
```

> **Order of operations:** adds and subtracts are applied first, then multiplies, then divides.
> So `(unit_price - discount) * quantity / installments` works as expected.

---

## Another Example — Payroll Calculator

```php
CalculatorAction::make('process_payroll')
    ->label('Process Payroll')
    ->calcSectionHeading('Payroll Breakdown')
    ->calcColumns(2)
    ->calcPrefix('USD')
    ->calcFields([
        CalcField::make('base_salary')
            ->label('Base Salary')
            ->adds()
            ->required()
            ->default(5000)
            ->columnSpan(1),
        CalcField::make('bonus')
            ->label('Bonus')
            ->adds()
            ->default(0)
            ->columnSpan(1),
        CalcField::make('deductions')
            ->label('Deductions')
            ->subtracts()
            ->default(0)
            ->columnSpan(1),
        CalcField::make('tax_withholding')
            ->label('Tax Withholding')
            ->subtracts()
            ->default(0)
            ->columnSpan(1),
        CalcField::make('net_salary')
            ->label('Net Salary')
            ->result(),
    ])
    ->action(function (array $data, $record) {
        $net = $this->computeResult($data);

        $record->payroll()->create([
            'base_salary'     => $data['base_salary'],
            'bonus'           => $data['bonus'],
            'deductions'      => $data['deductions'],
            'tax_withholding' => $data['tax_withholding'],
            'net_salary'      => $net,
        ]);
    })
```

---

## CalcField API

| Method | Description |
|--------|-------------|
| `CalcField::make(string $name)` | Create a field with the given key name |
| `->adds()` | Field value is **added** to the running total |
| `->subtracts()` | Field value is **subtracted** from the running total |
| `->multiplies()` | Running total is **multiplied** by this field's value |
| `->divides()` | Running total is **divided** by this field's value (zero-safe) |
| `->result()` | Marks this as the **read-only result** display field |
| `->label(string $label)` | Label shown above the input |
| `->prefix(string $prefix)` | Currency/unit prefix (e.g. `'EGP'`, `'$'`) — falls back to `calcPrefix()` |
| `->required(bool $required = true)` | Makes the field required on submit |
| `->default(float\|Closure $value)` | Default value; Closure receives `$record` |
| `->columnSpan(int $span)` | Grid column span within the calc section (default: `1`) |
| `->helperText(string $text)` | Small hint text displayed below the input |

---

## CalculatorAction / CalculatorPageAction API

| Method | Description |
|--------|-------------|
| `->calcFields(array $fields)` | Array of `CalcField` instances |
| `->calcSectionHeading(string $heading)` | Section heading for the calculator (default: `'Calculation'`) |
| `->calcColumns(int $columns)` | Column count for the calc section grid (default: `2`) |
| `->calcPrefix(string $prefix)` | Global prefix applied to all fields that don't define their own |
| `->computeResult(array $data)` | Server-side recalculation — use inside `->action()` |

---

## How it Works

Each non-result `CalcField` gets two HTML event attributes: `onkeyup` and `onchange`. Both run the same small inline script that reads all field values via `document.querySelector('[data-calc-field="name"]')`, computes the result, and updates the result field:

```js
// Generated JS (simplified)
var __r = ((unit_price) - (discount)) * (quantity);
var __t = document.querySelector('[data-calc-field="total"]');
if (__t) {
    __t.value = __r.toFixed(2);

    // Negative warning — red outline + red text
    if (__r < 0) {
        __t.style.color = '#dc2626';
        __t.style.outline = '2px solid #dc2626';
    } else {
        __t.style.color = '';
        __t.style.outline = '';
    }

    // Flash animation — yellow highlight fades out
    clearTimeout(window.__ct);
    __t.style.transition = 'background-color 0.4s';
    __t.style.backgroundColor = '#fef9c3';
    window.__ct = setTimeout(function () { __t.style.backgroundColor = ''; }, 400);
}
```

No framework dependency, no reactivity system, no round-trips. The result field is `readOnly` with `dehydrated(false)`, so Livewire does not include it in submitted form data.

### Negative total warning

When the computed result goes below zero, the result field turns **red** (color + outline) to signal the user to fix the inputs before submitting. The raw negative value is shown so the user understands what's wrong.

### Flash animation

Every time a value changes and a new result is computed, the result field briefly flashes **yellow** then fades back — giving clear visual feedback that the calculation fired.

---

## Server-Side Safety

**Always recompute the total server-side inside `->action()`** — never trust `$data['total']`.

Because the result field is `dehydrated(false)`, `$data['total']` will not be present in `$data`. Use the `computeResult()` helper or compute manually from the individual field values:

```php
->action(function (array $data) {
    // Option A — helper method
    $total = $this->computeResult($data);

    // Option B — manual (same result)
    $total = (float) ($data['subtotal'] ?? 0)
           + (float) ($data['extra_fees'] ?? 0)
           - (float) ($data['discount'] ?? 0);
})
```

Both options apply `max(0, ...)` clamping, preventing negative totals.

---

## License

MIT — see [LICENSE.md](LICENSE.md)