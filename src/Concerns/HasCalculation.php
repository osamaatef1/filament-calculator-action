<?php

namespace OsamaDev\FilamentCalculatorAction\Concerns;

use Filament\Forms\Components\TextInput;
use OsamaDev\FilamentCalculatorAction\CalcField;

trait HasCalculation
{
    protected array $calcFields = [];
    protected string $calcSectionHeading = 'Calculation';
    protected int $calcColumns = 2;
    protected ?string $calcPrefix = null;
    protected bool $calcFlash = true;
    protected string $calcFlashColor = '#fef9c3';
    protected int $calcFlashDuration = 400;

    public function calcFields(array $fields): static
    {
        $this->calcFields = $fields;

        return $this;
    }

    public function calcSectionHeading(string $heading): static
    {
        $this->calcSectionHeading = $heading;

        return $this;
    }

    public function calcColumns(int $columns): static
    {
        $this->calcColumns = $columns;

        return $this;
    }

    public function calcPrefix(string $prefix): static
    {
        $this->calcPrefix = $prefix;

        return $this;
    }

    public function calcFlash(bool $flash = true): static
    {
        $this->calcFlash = $flash;

        return $this;
    }

    public function calcFlashColor(string $color): static
    {
        $this->calcFlashColor = $color;

        return $this;
    }

    public function calcFlashDuration(int $ms): static
    {
        $this->calcFlashDuration = $ms;

        return $this;
    }

    public function buildCalcJs(): string
    {
        $resultField = null;
        $addParts = [];
        $subtractParts = [];
        $multiplyParts = [];
        $divideParts = [];

        foreach ($this->calcFields as $field) {
            if ($field->isResult()) {
                $resultField = $field->getName();
                continue;
            }

            $name = $field->getName();
            $role = $field->getRole();
            $fallback = $role === 'divide' ? '1' : '0';
            $expr = "(parseFloat(document.querySelector('[data-calc-field=\"{$name}\"]')?.value)||{$fallback})";

            match ($role) {
                'add'      => $addParts[] = $expr,
                'subtract' => $subtractParts[] = $expr,
                'multiply' => $multiplyParts[] = $expr,
                'divide'   => $divideParts[] = $expr,
                default    => null,
            };
        }

        if (! $resultField) {
            return '';
        }

        $addExpr = count($addParts) > 0 ? '(' . implode('+', $addParts) . ')' : '0';
        $subtractExpr = count($subtractParts) > 0 ? '(' . implode('+', $subtractParts) . ')' : '0';
        $resultExpr = "({$addExpr}-{$subtractExpr})";

        foreach ($multiplyParts as $part) {
            $resultExpr = "({$resultExpr}*{$part})";
        }

        foreach ($divideParts as $part) {
            $resultExpr = "({$resultExpr}/{$part})";
        }

        $sel = "[data-calc-field=\"{$resultField}\"]";

        $flashPart = '';

        if ($this->calcFlash) {
            $flashPart = ';clearTimeout(window.__ct)'
                . ';__t.style.transition=\'background-color ' . $this->calcFlashDuration . 'ms\''
                . ';__t.style.backgroundColor=\'' . $this->calcFlashColor . '\''
                . ';window.__ct=setTimeout(function(){__t.style.backgroundColor=\'\'}, ' . $this->calcFlashDuration . ')';
        }

        return "var __r={$resultExpr};var __t=document.querySelector('{$sel}');if(__t){__t.value=__r.toFixed(2);if(__r<0){__t.style.color='#dc2626';__t.style.outline='2px solid #dc2626'}else{__t.style.color='';__t.style.outline=''}{$flashPart}}";
    }

    /** @deprecated Use buildCalcJs() approach instead */
    public function buildAlpineData(): string
    {
        $properties = [];
        $addParts = [];
        $subtractParts = [];

        foreach ($this->calcFields as $field) {
            if ($field->isResult()) {
                continue;
            }

            $defaultValue = $field->resolveDefault(null);
            $properties[] = "{$field->getName()}: {$defaultValue}";

            if ($field->getRole() === 'add') {
                $addParts[] = "parseFloat(this.{$field->getName()} || 0)";
            } elseif ($field->getRole() === 'subtract') {
                $subtractParts[] = "parseFloat(this.{$field->getName()} || 0)";
            }
        }

        $addsExpression = count($addParts) > 0
            ? implode(' + ', $addParts)
            : '0';

        $expression = $addsExpression;
        foreach ($subtractParts as $subtractPart) {
            $expression .= " - {$subtractPart}";
        }

        $resultGetter = "get result() { return Math.max(0, {$expression}).toFixed(2) }";

        $allParts = array_merge($properties, [$resultGetter]);

        return '{ '.implode(', ', $allParts).' }';
    }

    public function buildCalcInputs(): array
    {
        $inputs = [];
        $js = $this->buildCalcJs();

        foreach ($this->calcFields as $field) {
            $prefix = $field->getPrefix() !== '' ? $field->getPrefix() : $this->calcPrefix;

            if ($field->isResult()) {
                $input = TextInput::make($field->getName())
                    ->label($field->getLabel())
                    ->readOnly()
                    ->dehydrated(false)
                    ->columnSpanFull()
                    ->extraInputAttributes(['data-calc-field' => $field->getName()]);

                if ($prefix !== null && $prefix !== '') {
                    $input->prefix($prefix);
                }

                $inputs[] = $input;
            } else {
                $name = $field->getName();
                $input = TextInput::make($name)
                    ->label($field->getLabel())
                    ->numeric()
                    ->extraInputAttributes([
                        'data-calc-field' => $name,
                        'onkeyup' => $js,
                        'onchange' => $js,
                    ]);

                if ($prefix !== null && $prefix !== '') {
                    $input->prefix($prefix);
                }

                if ($field->isRequired()) {
                    $input->required();
                }

                if ($field->getHelperText() !== '') {
                    $input->helperText($field->getHelperText());
                }

                $defaultValue = $field->getDefault();
                if ($defaultValue !== null) {
                    $input->default($defaultValue);
                }

                $input->columnSpan($field->getColumnSpan());

                $inputs[] = $input;
            }
        }

        return $inputs;
    }

    /**
     * Build the calculator Section component.
     *
     * Resolves the correct Section class at runtime:
     *   Filament v4 → Filament\Schemas\Components\Section
     *   Filament v3 → Filament\Forms\Components\Section
     */
    public function buildCalcSection(): object
    {
        $sectionClass = class_exists(\Filament\Schemas\Components\Section::class)
            ? \Filament\Schemas\Components\Section::class
            : \Filament\Forms\Components\Section::class;

        return $sectionClass::make($this->calcSectionHeading)
            ->columns($this->calcColumns)
            ->schema($this->buildCalcInputs());
    }

    /**
     * Wrap a user-supplied schema (array, Closure, or null) in a closure
     * that appends the calc section at the end.
     *
     * Safe to call from anonymous classes (no parent:: calls).
     */
    protected function wrapCalcSchema(array|\Closure|null $userSchema): \Closure
    {
        return function () use ($userSchema) {
            if ($userSchema instanceof \Closure) {
                $resolved = $userSchema();
                $components = is_array($resolved) ? $resolved : [];
            } elseif (is_array($userSchema)) {
                $components = $userSchema;
            } else {
                $components = [];
            }

            return array_merge($components, [$this->buildCalcSection()]);
        };
    }

    public function computeResult(array $data): float
    {
        $total = 0.0;

        foreach ($this->calcFields as $field) {
            if ($field->isResult()) {
                continue;
            }

            $value = (float) ($data[$field->getName()] ?? 0);

            match ($field->getRole()) {
                'add'      => $total += $value,
                'subtract' => $total -= $value,
                default    => null,
            };
        }

        foreach ($this->calcFields as $field) {
            if ($field->isResult()) {
                continue;
            }

            $value = (float) ($data[$field->getName()] ?? 0);

            match ($field->getRole()) {
                'multiply' => $total *= ($value ?: 1.0),
                'divide'   => $total /= ($value ?: 1.0),
                default    => null,
            };
        }

        return max(0.0, $total);
    }

    public function getCalcFields(): array
    {
        return $this->calcFields;
    }
}