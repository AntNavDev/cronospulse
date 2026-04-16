# Data Layer — CronosPulse

## Purpose

`app/Data/` contains readonly DTO (Data Transfer Object) classes. These are typed value objects that represent parsed API responses — the canonical data shape used throughout the application.

Services return collections of these objects. Livewire components convert them to arrays for public property storage (Livewire cannot serialize arbitrary objects).

## Rules

- All DTOs are `readonly` classes.
- Constructor parameters are typed strictly — cast at the service level, not here.
- Include domain-derived display helpers as methods (e.g. `magClass()`, `alertClass()`). These are computed from the object's own data and have no external dependencies.
- Include a `toArray()` method for Livewire public property storage. For data that requires runtime context (e.g. a user's timezone), accept it as a parameter.
- No database interaction, no API calls, no injection.

## Naming

DTO class names mirror the domain concept, not the API source: `EarthquakeData`, `VolcanoData`. Add `Data` suffix to avoid conflicts with Eloquent models of the same name.

## Current DTOs

| Class | Service | Notes |
|---|---|---|
| `EarthquakeData` | `EarthquakeService` | GeoJSON feature. `toArray(string $timezone)` includes formatted time. |
| `VolcanoData` | `VolcanoService` | VHP status record. `toArray()` includes computed badge classes. |
| `WaterServicesData` | `WaterServicesService` (pending) | One NWIS site × parameter time series. Holds latest reading value + datetime. `toArray(string $timezone)` includes formatted time and provisional flag. |