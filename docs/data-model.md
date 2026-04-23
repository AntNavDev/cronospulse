# Data Model

This document covers every table, every column, and how the models relate to each other.

---

## Entity Relationship Overview

```
users
 ├── saved_stations (user_id) ──► usgs_stations
 │                                    └── station_readings (station_id)
 └── saved_earthquake_searches (user_id)

earthquakes  (standalone — no foreign keys)
```

- A **user** can bookmark many **USGS stations** via `saved_stations`.
- A **user** can save up to 20 named earthquake searches via `saved_earthquake_searches`.
- A **USGS station** has many time-series **readings** (streamflow, water level, etc.).
- **Earthquakes** are independent seismic events with no station relationship.

---

## Tables

### `users`

Stores registered site users.

| Column | Type | Notes |
|---|---|---|
| `id` | `bigint unsigned` | Primary key |
| `name` | `varchar(255)` | Display name |
| `username` | `varchar(255)` | Unique. Used for login. Only letters, numbers, dashes, underscores (`alpha_dash`). |
| `email` | `varchar(255)` | Unique |
| `password` | `varchar(255)` | Bcrypt hash |
| `is_admin` | `tinyint(1)` | Default `false`. Gates admin-only features without a full roles package. |
| `email_verified_at` | `timestamp` | Nullable. Null = unverified. |
| `remember_token` | `varchar(100)` | Nullable. Laravel remember-me token. |
| `created_at` | `timestamp` | — |
| `updated_at` | `timestamp` | — |

**Model:** `App\Models\User`
**Relationships:**
- `savedStations()` → `HasMany<SavedStation>` — stations this user has bookmarked.

---

### `usgs_stations`

USGS monitoring locations. Shared by both water level and streamflow readings — a station can have readings for multiple parameter codes.

| Column | Type | Notes |
|---|---|---|
| `id` | `bigint unsigned` | Primary key |
| `site_no` | `varchar(20)` | Unique. USGS site number, e.g. `09380000` (Colorado River at Lee Ferry). |
| `name` | `varchar(255)` | Station name as returned by USGS |
| `state` | `char(2)` | Nullable. State abbreviation, e.g. `AZ`. Null for stations upserted via the detail page — requires a separate USGS Site Service call to populate. |
| `county` | `varchar(255)` | Nullable |
| `huc` | `varchar(16)` | Nullable. Hydrologic unit code — identifies the watershed. |
| `site_type` | `varchar(10)` | USGS site type code: `ST` (stream), `LK` (lake), `GW` (groundwater), etc. |
| `latitude` | `decimal(10,7)` | — |
| `longitude` | `decimal(10,7)` | — |
| `elevation_ft` | `decimal(10,2)` | Nullable. Elevation of the gauge in feet. |
| `is_active` | `tinyint(1)` | Default `true`. Inactive stations are kept for historical reads but hidden from live UI. |
| `created_at` | `timestamp` | — |
| `updated_at` | `timestamp` | — |

**Model:** `App\Models\UsgsStation`
**Relationships:**
- `readings()` → `HasMany<StationReading>` — all time-series readings for this station.
- `savedByUsers()` → `HasMany<SavedStation>` — bookmark records pointing to this station.

---

### `earthquakes`

Seismic events ingested from the USGS ComCat API. No foreign keys — earthquakes are not tied to a monitoring station.

| Column | Type | Notes |
|---|---|---|
| `id` | `bigint unsigned` | Primary key |
| `usgs_id` | `varchar(255)` | Unique. USGS ComCat event ID, e.g. `us6000abc1`. |
| `magnitude` | `decimal(4,2)` | Richter / moment magnitude value |
| `magnitude_type` | `varchar(10)` | Nullable. Scale used: `ml`, `mw`, `mb`, `md`, etc. |
| `depth_km` | `decimal(8,3)` | Hypocentral depth in kilometres |
| `latitude` | `decimal(10,7)` | Epicentre latitude |
| `longitude` | `decimal(10,7)` | Epicentre longitude |
| `place` | `varchar(255)` | Human-readable location, e.g. `10km NW of Ridgecrest, CA` |
| `status` | `varchar(20)` | `automatic` (machine-processed) or `reviewed` (analyst-confirmed). Default `automatic`. |
| `alert` | `varchar(10)` | Nullable. PAGER alert level: `green`, `yellow`, `orange`, `red`. |
| `felt` | `int unsigned` | Nullable. Number of "Did You Feel It?" reports submitted to USGS. |
| `cdi` | `decimal(3,1)` | Nullable. Maximum community decimal intensity reported. |
| `mmi` | `decimal(3,1)` | Nullable. Maximum instrumental intensity from ShakeMap (Modified Mercalli). |
| `significance` | `smallint unsigned` | Nullable. USGS significance score 0–1000 (accounts for magnitude, felt reports, PAGER level). |
| `url` | `varchar(255)` | Nullable. Link to the USGS event detail page. |
| `occurred_at` | `timestamp` | Origin time of the earthquake |
| `created_at` | `timestamp` | — |
| `updated_at` | `timestamp` | — |

**Indexes:** `occurred_at`, `magnitude` (both individually, to support time-range and magnitude-range queries).

**Model:** `App\Models\Earthquake`
**Relationships:** none.

---

### `station_readings`

Time-series observations from USGS stations. Rather than separate tables per data type, a single parameterised table is used. USGS identifies all data streams by a numeric parameter code, making this design naturally extensible — adding a new data type (e.g. turbidity, pH) requires no new migration.

| Column | Type | Notes |
|---|---|---|
| `id` | `bigint unsigned` | Primary key |
| `station_id` | `bigint unsigned` | Foreign key → `usgs_stations.id` (cascade delete) |
| `parameter_code` | `varchar(10)` | USGS parameter code. Common values: `00060` (discharge, ft³/s), `00065` (gage height, ft), `00010` (water temperature, °C). |
| `parameter_name` | `varchar(255)` | Human-readable label, e.g. `Discharge`, `Gage height` |
| `value` | `decimal(12,4)` | The observed measurement |
| `unit` | `varchar(50)` | Unit string as returned by USGS, e.g. `ft3/s`, `ft`, `degC` |
| `qualifier` | `varchar(10)` | Nullable. USGS data qualifier: `P` (provisional), `A` (approved), `e` (estimated). |
| `recorded_at` | `timestamp` | Observation datetime from USGS (not ingestion time) |
| `created_at` | `timestamp` | — |
| `updated_at` | `timestamp` | — |

**Unique constraint:** `(station_id, parameter_code, recorded_at)` — prevents duplicate ingestion of the same observation.
**Index:** same three columns for fast time-range queries per station per parameter.

**Model:** `App\Models\StationReading`
**Relationships:**
- `station()` → `BelongsTo<UsgsStation>` — the station this reading belongs to.

---

### `saved_stations`

User bookmarks. Allows a user to follow specific USGS stations and surface them in their dashboard.

| Column | Type | Notes |
|---|---|---|
| `id` | `bigint unsigned` | Primary key |
| `user_id` | `bigint unsigned` | Foreign key → `users.id` (cascade delete) |
| `station_id` | `bigint unsigned` | Foreign key → `usgs_stations.id` (cascade delete) |
| `created_at` | `timestamp` | — |
| `updated_at` | `timestamp` | — |

**Unique constraint:** `(user_id, station_id)` — a user cannot bookmark the same station twice.

**Model:** `App\Models\SavedStation`
**Relationships:**
- `user()` → `BelongsTo<User>`
- `station()` → `BelongsTo<UsgsStation>`

---

### `saved_earthquake_searches`

Named earthquake search bookmarks. Stores the map-click parameters so a user can re-run a QuakeWatch search from their dashboard.

| Column | Type | Notes |
|---|---|---|
| `id` | `bigint unsigned` | Primary key |
| `user_id` | `bigint unsigned` | Foreign key → `users.id` (cascade delete) |
| `name` | `varchar(100)` | User-supplied label, e.g. `Bay Area M3+` |
| `latitude` | `decimal(10,7)` | Search centre latitude |
| `longitude` | `decimal(10,7)` | Search centre longitude |
| `radius_km` | `decimal(8,2)` | Search radius in kilometres |
| `min_magnitude` | `decimal(3,1)` | Minimum magnitude filter. `0.0` = any magnitude. Default `0.0`. |
| `created_at` | `timestamp` | — |
| `updated_at` | `timestamp` | — |

**Constraint:** max 20 rows per user (enforced in `QuakeWatch::saveSearch()`, not at the DB level).

**Model:** `App\Models\SavedEarthquakeSearch`
**Relationships:**
- `user()` → `BelongsTo<User>`

---

## Cascade Behaviour

| Deleted record | Effect |
|---|---|
| `users` row deleted | All `saved_stations` and `saved_earthquake_searches` rows for that user are deleted |
| `usgs_stations` row deleted | All `station_readings` and `saved_stations` rows for that station are deleted |

Earthquakes have no foreign keys and are unaffected by deletions elsewhere.