<?php

namespace OsamaDev\FilamentCalculatorAction\Concerns;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use OsamaDev\FilamentCalculatorAction\CalcField;

trait HasCalculation
{
    protected array $calcFields = [];
    protected string $calcSectionHeading = 'Calculation';
    protected int $calcColumns = 2;
    protected ?string $calcPrefix = null;

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

    public function buildCalcJs(): string
    {
        $resultField = null;
        $addParts = [];
        $subtractParts = [];

        foreach ($this->calcFields as $field) {
            if ($field->isResult()) {
                $resultField = $field->getName();
                continue;
            }

            $expr = "(parseFloat(document.querySelector('[data-calc-field=\"{$field->getName()}\"]')?.value)||0)";

            if ($field->getRole() === 'add') {
                $addParts[] = $expr;
            } elseif ($field->getRole() === 'subtract') {
                $subtractParts[] = $expr;
            }
        }

        if (! $resultField) {
            return '';
        }

        $addExpr = count($addParts) > 0 ? implode('+', $addParts) : '0';
        $subtractExpr = count($subtractParts) > 0 ? '('.implode('+', $subtractParts).')' : '0';

        return "var __t=document.querySelector('[data-calc-field=\"{$resultField}\"]');if(__t)__t.value=Math.max(0,({$addExpr})-{$subtractExpr}).toFixed(2)";
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

    public function buildCalcSection(): Section
    {
        return Section::make($this->calcSectionHeading)
            ->columns($this->calcColumns)
            ->schema($this->buildCalcInputs());
    }

    public function computeResult(array $data): float
    {
        $total = 0.0;

        foreach ($this->calcFields as $field) {
            if ($field->isResult()) {
                continue;
            }

            $value = (float) ($data[$field->getName()] ?? 0);

            if ($field->getRole() === 'add') {
                $total += $value;
            } elseif ($field->getRole() === 'subtract') {
                $total -= $value;
            }
        }

        return max(0.0, $total);
    }

    public function getCalcFields(): array
    {
        return $this->calcFields;
    }
}