<?php

namespace OsamaDev\FilamentCalculatorAction;

use OsamaDev\FilamentCalculatorAction\Concerns\HasCalculation;

/**
 * @extends \OsamaDev\FilamentCalculatorAction\ActionBase
 *
 * ActionBase resolves to:
 *   Filament v3 → Filament\Tables\Actions\Action
 *   Filament v4 → Filament\Actions\Action
 */
class CalculatorAction extends ActionBase
{
    use HasCalculation;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialise with an empty user-schema so the calc section always appears
        // even when the user never calls ->form() / ->schema().
        $this->pushCalcSection(null);
    }

    /**
     * Intercept ->form([...]) so the calc section is always appended.
     * Works for both v3 (stores in $this->form) and v4 (delegates to schema()).
     */
    public function form(array|\Closure|null $schema): static
    {
        return $this->pushCalcSection($schema);
    }

    /**
     * Intercept ->schema([...]) (Filament v4 API).
     * In v3 this method doesn't exist on the parent, but our override
     * still works — we just route it through form() internally.
     */
    public function schema(array|\Closure|null $schema): static
    {
        return $this->pushCalcSection($schema);
    }

    /**
     * Wrap $userSchema in a closure that appends buildCalcSection(),
     * then hand it off to the real parent setter (schema() on v4, form() on v3).
     */
    private function pushCalcSection(array|\Closure|null $userSchema): static
    {
        $wrapped = $this->wrapCalcSchema($userSchema);

        // v4: parent has schema() — use it to avoid the deprecated form() wrapper.
        // v3: parent only has form().
        if (method_exists(get_parent_class(static::class), 'schema')) {
            return parent::schema($wrapped);
        }

        return parent::form($wrapped);
    }
}
