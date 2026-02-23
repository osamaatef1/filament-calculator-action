<?php

namespace OsamaDev\FilamentCalculatorAction;

use Filament\Actions\Action;
use OsamaDev\FilamentCalculatorAction\Concerns\HasCalculation;

/**
 * Page-header variant of CalculatorAction.
 *
 * Extends Filament\Actions\Action which exists in both v3 and v4,
 * so no bootstrap alias is needed here.
 */
class CalculatorPageAction extends Action
{
    use HasCalculation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pushCalcSection(null);
    }

    public function form(array|\Closure|null $schema): static
    {
        return $this->pushCalcSection($schema);
    }

    public function schema(array|\Closure|null $schema): static
    {
        return $this->pushCalcSection($schema);
    }

    private function pushCalcSection(array|\Closure|null $userSchema): static
    {
        $wrapped = $this->wrapCalcSchema($userSchema);

        if (method_exists(get_parent_class(static::class), 'schema')) {
            return parent::schema($wrapped);
        }

        return parent::form($wrapped);
    }
}
