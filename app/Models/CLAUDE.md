# Model Conventions — CronosPulse

## General Rules

- All models extend `Illuminate\Database\Eloquent\Model` (or `Authenticatable` for `User`).
- Every file must have `declare(strict_types=1)`.
- Use `protected $fillable` arrays — not PHP 8 `#[Fillable]` attributes.
- Cast all non-string primitives explicitly in `casts()`: booleans, floats, integers, datetimes.
- Do not define `$guarded`. Mass assignment protection is handled entirely by `$fillable`.
- No soft deletes unless the table has a `deleted_at` column in its migration.

## Relationships

- Define the inverse (`belongsTo`) on the child model and the forward (`hasMany`/`hasOne`) on the parent.
- Always supply explicit foreign key strings when the key does not follow Laravel's convention (e.g. `station_id` on models whose table is not named `stations`).
- Use typed PHPDoc generics on every relation: `@return HasMany<StationReading, $this>`.

## USGS Data Models

| Model | Table | Notes |
|---|---|---|
| `UsgsStation` | `usgs_stations` | Monitoring locations shared by water level and streamflow. `site_no` is the USGS site number. |
| `StationReading` | `station_readings` | Unified parameterised readings. Use `parameter_code` (e.g. `00060`) to distinguish streamflow from water level rather than separate tables. |
| `Earthquake` | `earthquakes` | Seismic events from USGS ComCat. `usgs_id` is the unique event ID. |
| `SavedStation` | `saved_stations` | User bookmarks. Unique on `[user_id, station_id]`. |

## Naming

- Model class names are singular PascalCase: `UsgsStation`, `StationReading`.
- Relation methods are camelCase and named after the related model: `station()`, `readings()`, `savedStations()`.