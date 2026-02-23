<?php

/**
 * Resolve the correct Action base class for CalculatorAction.
 *
 * Filament v3: Filament\Tables\Actions\Action  (table-specific action with BelongsToTable etc.)
 * Filament v4: Filament\Actions\Action          (unified — no separate Tables\Actions\Action)
 */
if (! class_exists('OsamaDev\FilamentCalculatorAction\ActionBase')) {
    if (class_exists(\Filament\Tables\Actions\Action::class)) {
        class_alias(\Filament\Tables\Actions\Action::class, 'OsamaDev\FilamentCalculatorAction\ActionBase');
    } else {
        class_alias(\Filament\Actions\Action::class, 'OsamaDev\FilamentCalculatorAction\ActionBase');
    }
}
