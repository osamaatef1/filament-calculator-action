# Changelog

All notable changes to `filament-calculator-action` will be documented in this file.

## [Unreleased]

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
