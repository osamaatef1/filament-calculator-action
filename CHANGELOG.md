# Changelog

All notable changes to `filament-calculator-action` will be documented in this file.

## [Unreleased]

## [1.1.0] - 2026-02-22

### Added
- Filament v4 compatibility (`filament/filament ^3.0|^4.0`)
- `src/bootstrap.php` — runtime class alias resolves `ActionBase` to `Filament\Tables\Actions\Action` (v3) or `Filament\Actions\Action` (v4)
- `CalculatorPageAction` — page-header action variant (extends `Filament\Actions\Action`, works in both v3 and v4)
- `CalcField::multiplies()` and `CalcField::divides()` roles with server-side support in `computeResult()`
- `calcFlash()`, `calcFlashColor()`, `calcFlashDuration()` — configurable yellow flash animation on result field
- Negative total warning — result field turns red when below zero

### Changed
- JS approach switched from Alpine.js `x-data` to plain `onkeyup`/`onchange` with `data-calc-field` selectors (scope-safe, no Alpine dependency)
- `CalculatorAction` now intercepts `form()` and `schema()` setters instead of overriding `getForm()` (required for v4 signature compatibility)
- `HasCalculation::buildCalcSection()` detects Section class at runtime (`Filament\Schemas\Components\Section` for v4, `Filament\Forms\Components\Section` for v3)

## [1.0.1] - 2026-02-22

### Changed
- Extended `illuminate/support` requirement to include `^12.0` (Laravel 12 support)
- Extended `orchestra/testbench` dev requirement to include `^10.0`

## [1.0.0] - 2026-02-22

### Added
- Initial release
- `CalculatorAction` — Filament v3 Table Action with Alpine.js real-time calculation
- `CalcField` — fluent field builder supporting `adds()`, `subtracts()`, and `result()` roles
- `HasCalculation` trait — `buildAlpineData()`, `buildCalcSection()`, `computeResult()`
- Server-side safety via `computeResult()` recomputation
- Global `calcPrefix()` fallback with per-field override
- Full Pest test suite (18 tests)
