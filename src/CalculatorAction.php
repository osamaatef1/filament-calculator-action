<?php

namespace OsamaDev\FilamentCalculatorAction;

use Filament\Tables\Actions\Action;
use OsamaDev\FilamentCalculatorAction\Concerns\HasCalculation;

class CalculatorAction extends Action
{
    use HasCalculation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->form(function (array $schema): array {
            return array_merge($schema, [$this->buildCalcSection()]);
        });
    }
}
