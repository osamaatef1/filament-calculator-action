# filament-calculator-action

[![Latest Version on Packagist](https://img.shields.io/packagist/v/osama-dev/filament-calculator-action.svg?style=flat-square)](https://packagist.org/packages/osama-dev/filament-calculator-action)
[![License](https://img.shields.io/packagist/l/osama-dev/filament-calculator-action.svg?style=flat-square)](LICENSE.md)

A dynamic real-time calculator action for **Filament v3** powered by **Alpine.js** — zero Livewire round-trips, instant client-side calculation.

---

## Why this package?

### The problem

In Filament v3, wiring form fields with `->live()` causes a Livewire round-trip on every keystroke. For a simple numeric calculator (subtotal + fees − discount = total), this means a **~2-second server call** every time the user types a digit. This makes the UX feel sluggish and unresponsive.

### The solution

`filament-calculator-action` generates an **Alpine.js `x-data` scope** directly on the form section. All arithmetic happens entirely in the browser — no server call, no debounce, no waiting. The `get result()` getter in Alpine re-evaluates on every field change, giving true instant feedback.

Server-side recomputation via `computeResult()` is still performed inside `->action()` to ensure the final stored value is trustworthy.

---

## Installation

```bash
composer require osama-dev/filament-calculator-action
```

---

## Basic Usage — Receipt / Invoice Example

```php
use OsamaDev\FilamentCalculatorAction\CalculatorAction;
use OsamaDev\FilamentCalculatorAction\CalcField;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

CalculatorAction::make('issue_receipt')
    ->label('Mark Fulfilled & Issue Receipt')
    ->icon('heroicon-o-document-text')
    ->color('success')
    ->visible(fn (ServiceBooking $record): bool =>
        $record->status === BookingType::IN_PROGRESS->value &&
        auth()->user()->can('issue_receipt_service::booking')
    )
    ->form([
        Section::make('Booking Context')
            ->schema([
                TextInput::make('user_name')
                    ->label('User')
                    ->default(fn ($record) => $record->user
                        ? $record->user->first_name . ' ' . $record->user->last_name
                        : 'Deleted User')
                    ->disabled(),
                TextInput::make('unit_code')
                    ->label('Unit Code')
                    ->default(fn ($record) => $record->unit->code)
                    ->disabled(),
                TextInput::make('service_name')
                    ->label('Service Name')
                    ->default(fn ($record) => $record->service->name)
                    ->disabled(),
                TextInput::make('request_id')
                    ->label('Request ID')
                    ->default(fn ($record) => $record->booking_reference)
                    ->disabled(),
            ])->columns(2),
        Textarea::make('notes')
            ->columnSpan(2)
            ->required(fn (callable $get) => $get('extra_fees') > 0)
            ->label('Notes')
            ->rows(3),
    ])
    ->calcSectionHeading('Receipt Details')
    ->calcColumns(2)
    ->calcPrefix('EGP')
    ->calcFields([
        CalcField::make('subtotal')
            ->adds()
            ->label('Subtotal')
            ->prefix('EGP')
            ->required()
            ->helperText('including taxes')
            ->default(fn ($record) => $record->subService->starting_price ?? 0),
        CalcField::make('extra_fees')
            ->adds()
            ->label('Extra Fees')
            ->prefix('EGP')
            ->default(0),
        CalcField::make('discount')
            ->subtracts()
            ->label('Discount')
            ->prefix('EGP')
            ->default(0),
        CalcField::make('total')
            ->result()
            ->label('Total')
            ->prefix('EGP'),
    ])
    ->action(function (array $data, $record) {
        $total = $this->computeResult($data);
        $status = $total == 0 ? InvoiceType::PAID->value : InvoiceType::DUE->value;

        $record->update(['status' => BookingType::FULFILLED->value]);
        $record->invoice()->create([
            'user_id'        => $record->user_id,
            'unit_id'        => $record->unit_id,
            'service_id'     => $record->service_id,
            'sub_service_id' => $record->sub_service_id,
            'subtotal'       => $data['subtotal'],
            'discount'       => $data['discount'],
            'extra_fees'     => $data['extra_fees'],
            'total'          => $total,
            'status'         => $status,
            'notes'          => $data['notes'] ?? null,
        ]);

        Notification::make()
            ->title('Receipt Issued')
            ->body('Receipt has been successfully created.')
            ->success()
            ->send();
    })
    ->modalHeading('Issue Receipt')
    ->modalSubmitActionLabel('Create Receipt')
```

---

## Another Example — Payroll Calculator

```php
CalculatorAction::make('calculate_payroll')
    ->label('Process Payroll')
    ->calcSectionHeading('Payroll Breakdown')
    ->calcColumns(2)
    ->calcPrefix('USD')
    ->calcFields([
        CalcField::make('base_salary')
            ->adds()
            ->label('Base Salary')
            ->required()
            ->default(5000),
        CalcField::make('bonus')
            ->adds()
            ->label('Bonus')
            ->default(0),
        CalcField::make('deductions')
            ->subtracts()
            ->label('Deductions')
            ->default(0),
        CalcField::make('tax_withholding')
            ->subtracts()
            ->label('Tax Withholding')
            ->default(0),
        CalcField::make('net_salary')
            ->result()
            ->label('Net Salary'),
    ])
    ->action(function (array $data, $record) {
        $netSalary = $this->computeResult($data);

        $record->payroll()->create([
            'base_salary'     => $data['base_salary'],
            'bonus'           => $data['bonus'],
            'deductions'      => $data['deductions'],
            'tax_withholding' => $data['tax_withholding'],
            'net_salary'      => $netSalary,
        ]);
    })
```

---

## CalcField API

| Method | Description |
|--------|-------------|
| `CalcField::make(string $name)` | Create a new field with the given name |
| `->adds()` | This field's value is **added** to the total |
| `->subtracts()` | This field's value is **subtracted** from the total |
| `->result()` | Marks this field as the **read-only result** display |
| `->label(string $label)` | Human-readable label shown above the input |
| `->prefix(string $prefix)` | Currency/unit prefix (e.g. `'EGP'`, `'$'`) |
| `->required(bool $required = true)` | Makes the field required for form submission |
| `->default(float\|Closure $value)` | Default value; Closure receives `$record` |
| `->columnSpan(int\|string $span)` | Grid column span (default: `1`) |
| `->helperText(string $text)` | Small hint text below the field |

### Example

```php
CalcField::make('subtotal')
    ->adds()
    ->label('Subtotal')
    ->prefix('EGP')
    ->required()
    ->helperText('Base price before adjustments')
    ->default(fn ($record) => $record->base_price ?? 0)
```

---

## CalculatorAction API

| Method | Description |
|--------|-------------|
| `->calcFields(array $fields)` | Array of `CalcField` instances defining the calculator |
| `->calcSectionHeading(string $heading)` | Heading for the generated calculator section (default: `'Calculation'`) |
| `->calcColumns(int $columns)` | Number of grid columns in the calc section (default: `2`) |
| `->calcPrefix(string $prefix)` | Global currency/unit prefix fallback for fields with no explicit prefix |
| `->computeResult(array $data)` | Server-side recomputation — use this in `->action()` to get the trusted total |

---

## How it Works

When `CalculatorAction` builds the form, it generates an Alpine.js `x-data` attribute on the calculator section. The data object looks like:

```js
{
  subtotal: 0,
  extra_fees: 0,
  discount: 0,
  get result() {
    return Math.max(0, parseFloat(this.subtotal || 0) + parseFloat(this.extra_fees || 0) - (parseFloat(this.discount || 0))).toFixed(2)
  }
}
```

Each input field is bound via `x-model.number`, and the result field reads from the reactive `result` getter via `x-bind:value`. Because all variables live in the same Alpine scope, changes propagate instantly with **zero server involvement**.

---

## Server-Side Safety

**Always use `computeResult($data)` inside `->action()`** — never read the `total` field directly from `$data`.

The result field is rendered with `->dehydrated(false)`, meaning Livewire does **not** submit it with the form. The client-computed display value is for UX only. The authoritative total is always recalculated server-side from the submitted numeric fields, protecting against:

- Client-side manipulation
- JavaScript errors or edge cases
- Browser compatibility issues with Alpine expressions

```php
->action(function (array $data) {
    $total = $this->computeResult($data); // always use this
    // $data['total'] is NOT available — use computeResult()
})
```

---

## License

MIT — see [LICENSE.md](LICENSE.md)
