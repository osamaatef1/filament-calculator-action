<?php

namespace OsamaDev\FilamentCalculatorAction;

use Filament\Actions\Action;
use Filament\Forms\Form;
use OsamaDev\FilamentCalculatorAction\Concerns\HasCalculation;

class CalculatorPageAction extends Action
{
    use HasCalculation;

    public function getForm(Form $form): ?Form
    {
        $userForm = $this->form;

        if (is_array($userForm)) {
            $schema = $userForm;
        } elseif ($userForm instanceof \Closure) {
            $evaluated = $this->evaluate($userForm, ['form' => $form]);
            $schema = is_array($evaluated) ? $evaluated : [];
        } else {
            $schema = [];
        }

        $this->form = array_merge($schema, [$this->buildCalcSection()]);

        $result = parent::getForm($form);

        $this->form = $userForm;

        return $result;
    }
}