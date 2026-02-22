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

        return '{ ' . implode(', ', $allParts) . ' }';
    }

    public function buildCalcInputs(): array
    {
        $inputs = [];

        foreach ($this->calcFields as $field) {
            $prefix = $field->getPrefix() !== '' ? $field->getPrefix() : $this->calcPrefix;

            if ($field->isResult()) {
                $input = TextInput::make($field->getName())
                    ->label($field->getLabel())
                    ->readOnly()
                    ->dehydrated(false)
                    ->columnSpanFull()
                    ->extraInputAttributes(['x-bind:value' => 'result']);

                if ($prefix !== null && $prefix !== '') {
                    $input->prefix($prefix);
                }

                $inputs[] = $input;
            } else {
                $input = TextInput::make($field->getName())
                    ->label($field->getLabel())
                    ->numeric()
                    ->extraInputAttributes(['x-model.number' => $field->getName()]);

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
                    if ($defaultValue instanceof \Closure) {
                        $input->default($defaultValue);
                    } else {
                        $input->default($defaultValue);
                    }
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
            ->schema($this->buildCalcInputs())
            ->extraAttributes(['x-data' => $this->buildAlpineData()]);
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
