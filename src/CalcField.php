<?php

namespace OsamaDev\FilamentCalculatorAction;

use Closure;

class CalcField
{
    protected string $name;
    protected string $role = 'add';
    protected string $label = '';
    protected string $prefix = '';
    protected bool $required = false;
    protected int|string $columnSpan = 1;
    protected string $helperText = '';
    protected float|Closure|null $default = null;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->label = $name;
    }

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function adds(): static
    {
        $this->role = 'add';

        return $this;
    }

    public function subtracts(): static
    {
        $this->role = 'subtract';

        return $this;
    }

    public function result(): static
    {
        $this->role = 'result';

        return $this;
    }

    public function default(float|Closure $value): static
    {
        $this->default = $value;

        return $this;
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function prefix(string $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    public function columnSpan(int|string $span): static
    {
        $this->columnSpan = $span;

        return $this;
    }

    public function helperText(string $text): static
    {
        $this->helperText = $text;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getColumnSpan(): int|string
    {
        return $this->columnSpan;
    }

    public function getHelperText(): string
    {
        return $this->helperText;
    }

    public function getDefault(): float|Closure|null
    {
        return $this->default;
    }

    public function isResult(): bool
    {
        return $this->role === 'result';
    }

    public function resolveDefault(mixed $record = null): float
    {
        if ($this->default instanceof Closure) {
            return (float) ($this->default)($record);
        }

        return (float) ($this->default ?? 0);
    }
}
