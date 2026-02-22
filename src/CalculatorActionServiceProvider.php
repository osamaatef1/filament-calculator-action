<?php

namespace OsamaDev\FilamentCalculatorAction;

use Filament\Forms\Components\TextInput;
use Illuminate\Support\ServiceProvider;

class CalculatorActionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // $this->publishes([
        //     __DIR__.'/../config/filament-calculator-action.php' => config_path('filament-calculator-action.php'),
        // ], 'filament-calculator-action-config');

        TextInput::macro('getPrefix', function (): mixed {
            /** @var TextInput $this */
            return $this->getPrefixLabel();
        });
    }

    public function register(): void
    {
        //
    }
}
