# AGENTS.md

This file gives coding agents the context and guardrails needed to work effectively in this repository.

## Project Overview

`wp-plugin-stamdata` is a WordPress plugin that acts as a central master-data layer for a sports website.  
Other plugins should be able to use this plugin as the single source of truth for shared site-wide entities.

The plugin stores and exposes the following master data:

- `Wedstrijden` (`matches`)
- `Teams`
- `Spelers` (`players`)
- `Coaches`
- `Locaties` (`locations`)
- `Velden` (`fields`)

Every entity must have its own dedicated database table.

## Primary Goal

Build a reusable and maintainable WordPress plugin that:

- owns the master data for the website
- stores each entity in a separate custom database table
- offers a stable API for other plugins to read this data
- keeps WordPress admin management straightforward
- is safe to evolve over time with schema migrations
- supports both live data and test/local-editable data workflows

## Product Intent

This plugin is not meant to be a one-off feature plugin.  
It should become a foundational data plugin that other plugins can depend on.

That means:

- data access should be centralized
- table schemas should be explicit and versioned
- business logic should live inside this plugin, not be duplicated elsewhere
- external consumers should use plugin functions/services/hooks instead of querying tables directly when possible
- the plugin must distinguish between production/live data usage and local/test data usage

## Domain Model

Use Dutch naming in user-facing labels where appropriate, but keep PHP code, class names, methods, and internal identifiers consistent and readable in English.

Recommended entity mapping:

- `Wedstrijden` -> matches
- `Teams` -> teams
- `Spelers` -> players
- `Coaches` -> coaches
- `Locaties` -> locations
- `Velden` -> fields

Note:

- sporthal information is not part of the current active scope
- if added later, it should describe the club's own sporthal metadata rather than external venues

## Required Database Tables

Use one table per entity, with the WordPress table prefix:

- `{$wpdb->prefix}stamdata_matches`
- `{$wpdb->prefix}stamdata_teams`
- `{$wpdb->prefix}stamdata_players`
- `{$wpdb->prefix}stamdata_coaches`
- `{$wpdb->prefix}stamdata_locations`
- `{$wpdb->prefix}stamdata_fields`

Use `dbDelta()` for creation and controlled upgrades.

Also maintain a plugin schema version option, for example:

- option key: `wp_plugin_stamdata_db_version`

## Data Modes: Live And Test

This plugin must support two data modes:

- `live` data
- `test` data

Intent:

- on the real/production site, the plugin should always use the `live` version of the data
- in local development, data should be easy to change manually for testing and development purposes
- other plugins should ideally consume data through an API layer that can respect the active data mode

Recommended behavior:

- production environment: always resolve reads to `live` data
- local environment: allow developers/admins to work with `test` data easily
- if useful, support fallback rules explicitly in code rather than making data-source behavior implicit

Implementation direction:

- keep the distinction between `live` and `test` deliberate and visible in schema and code
- prefer a simple approach such as a `data_version` or `data_mode` column on relevant tables unless a stronger reason appears for separate version tables
- make sure queries used by other plugins can consistently request the correct version of the data
- do not let local test changes accidentally overwrite production live data rules

Example design options:

- add a `data_version` column with values like `live` and `test`
- or create dedicated shadow/version tables only if the simpler column-based approach becomes too limiting

Default recommendation:

- start with a `data_version` column on each master-data table
- use repository methods/services to filter by the correct version
- keep production locked to `live`

## Suggested Table Design

Keep schemas practical and normalized enough for reuse, without over-engineering.

### `stamdata_teams`

Suggested columns:

- `id` bigint unsigned, primary key
- `name` varchar
- `slug` varchar
- `created_at` datetime
- `updated_at` datetime

### `stamdata_players`

Suggested columns:

- `id` bigint unsigned, primary key
- `team_id` bigint unsigned, nullable or required depending on business rules
- `first_name` varchar
- `last_name` varchar
- `display_name` varchar
- `date_of_birth` date, nullable
- `jersey_number` int, nullable
- `position` varchar, nullable
- `email` varchar, nullable
- `phone` varchar, nullable
- `status` varchar, nullable
- `created_at` datetime
- `updated_at` datetime

### `stamdata_coaches`

Suggested columns:

- `id` bigint unsigned, primary key
- `team_id` bigint unsigned, nullable
- `first_name` varchar
- `last_name` varchar
- `display_name` varchar
- `email` varchar, nullable
- `phone` varchar, nullable
- `role` varchar, nullable
- `status` varchar, nullable
- `created_at` datetime
- `updated_at` datetime

### `stamdata_matches`

Suggested columns:

- `id` bigint unsigned, primary key
- `home_team_id` bigint unsigned
- `away_team_id` bigint unsigned
- `match_date` datetime
- `competition` varchar, nullable
- `season` varchar, nullable
- `status` varchar, nullable
- `score_home` int, nullable
- `score_away` int, nullable
- `notes` text, nullable
- `created_at` datetime
- `updated_at` datetime

### `stamdata_locations`

Suggested columns:

- `id` bigint unsigned, primary key
- `name` varchar
- `slug` varchar
- `address` varchar, nullable
- `city` varchar, nullable
- `created_at` datetime
- `updated_at` datetime

`Locaties` are physical locations.

### `stamdata_fields`

Suggested columns:

- `id` bigint unsigned, primary key
- `location_id` bigint unsigned
- `name` varchar
- `slug` varchar
- `sort_order` int, nullable
- `created_at` datetime
- `updated_at` datetime

`Velden` are fields/courts/pitches that belong to a location.

## Relationships

Use logical relationships in code, even if MySQL foreign keys are not enforced in every environment.

Recommended relationships:

- players belong to teams
- coaches can belong to teams
- matches belong to a home team and an away team
- fields belong to locations

Avoid hard-coupling WordPress posts to the core data model unless there is a strong product reason.

When using `live` and `test` data, relationships should stay within the same data version whenever possible.  
For example, a `test` match should reference `test` teams and a `live` match should reference `live` teams.

## WordPress Implementation Rules

- Follow WordPress coding standards for PHP.
- Use `$wpdb` for custom table access.
- Use `dbDelta()` for table creation and schema updates.
- Sanitize all input and escape all output.
- Use nonces and capability checks for admin actions.
- Wrap all direct file access with `defined( 'ABSPATH' ) || exit;`
- Prefer hooks, service classes, and repository-style access over scattered SQL.
- Keep side effects out of template files.
- Environment-aware behavior must be explicit and easy to audit.

## Architectural Direction

Favor a small, organized plugin architecture instead of putting everything in one file.

Suggested structure:

```text
wp-plugin-stamdata/
├── wp-plugin-stamdata.php
├── AGENTS.md
├── README.md
├── includes/
│   ├── class-plugin.php
│   ├── class-installer.php
│   ├── class-schema.php
│   ├── repositories/
│   │   ├── class-team-repository.php
│   │   ├── class-player-repository.php
│   │   ├── class-coach-repository.php
│   │   ├── class-location-repository.php
│   │   ├── class-field-repository.php
│   │   └── class-match-repository.php
│   ├── services/
│   └── admin/
└── assets/
```

## API Strategy For Other Plugins

Because this plugin is a shared data layer, expose stable access patterns.

Preferred options:

- public PHP functions with a clear prefix, for example `wp_plugin_stamdata_get_team( $id )`
- service/repository classes behind a bootstrap container
- WordPress filters/actions for extension points
- optional REST API endpoints later if needed

These access patterns should hide the data-mode complexity from consuming plugins where possible.  
Consuming plugins should not have to manually decide SQL rules for `live` vs `test` on every query.

Avoid encouraging direct SQL queries from consuming plugins unless no internal API exists yet.

## Naming Conventions

- Prefix PHP functions, constants, options, and hooks with `wp_plugin_stamdata_`
- Prefix classes consistently, for example `WP_Plugin_Stamdata_*`
- Prefix database tables with `stamdata_` after `$wpdb->prefix`
- Keep filenames aligned with WordPress conventions if using class-based includes

## Admin Direction

When admin screens are built:

- keep labels in Dutch for site managers if that matches the product language
- keep code internals in English for maintainability
- make CRUD flows simple and explicit
- validate required relations before saving records
- provide helpful empty states and error messages
- make it very clear whether an admin is editing `live` data or `test` data
- optimize local/test editing flows so changing data is quick and low-friction
- each entity should have its own admin area and submenu entry, for example `Teams`, `Players`, `Coaches`, `Locaties`, `Velden`, and `Matches`
- use a dedicated list page per entity for overview and management
- use a separate add/edit page for each entity instead of combining form editing into the list screen
- keep add/edit pages out of the sidebar; only the entity overview/list page should appear in the menu
- provide an `Add new` button from the entity list page and `Edit` links from the table/list rows
- future entities should follow the same admin UX pattern as `Teams`

## External API Sync

Live data will mostly come from external APIs.

Expectations:

- external APIs are the primary source for most `live` data
- live data should be queried and synchronized every night
- synchronization should be automated with a scheduled WordPress cron task
- sync logic should be idempotent and safe to rerun
- failures should be logged clearly for debugging

Recommended implementation direction:

- create dedicated importer/sync services for each external data source
- schedule a nightly cron event, for example once per day during off-peak hours
- upsert records instead of blindly deleting and reinserting everything unless there is a strong reason
- keep mapping logic between external payloads and internal tables centralized
- separate fetch logic, transform logic, and persistence logic

Production rule:

- the real site should always use `live` data
- nightly API sync updates the `live` dataset
- local/test edits should not change the production rule that the real site uses `live`

## Data Integrity Expectations

- Create unique indexes where useful, such as team slugs
- Add normal indexes for common lookups like `team_id`, `location_id`, and match date
- If using `data_version`, include it in indexes where it affects common lookups
- Handle deletion carefully; prefer soft constraints in logic over unsafe cascades
- Think through what happens when a team is removed but players, coaches, or matches still reference it

## Backward Compatibility

This plugin should evolve carefully because other plugins may depend on it.

When changing schema or APIs:

- prefer additive changes first
- write migrations deliberately
- do not rename public hooks/functions without a compatibility layer
- document breaking changes in `README.md`

## What Agents Should Optimize For

When working in this repository, prioritize:

1. clear data ownership
2. maintainable schema design
3. stable integration points for other plugins
4. WordPress-native safety and compatibility
5. readable code over clever abstractions
6. predictable handling of `live` versus `test` data

## What Agents Should Avoid

- storing this master data as plain options when it belongs in tables
- mixing unrelated business logic into templates
- creating hidden cross-plugin dependencies
- bypassing sanitization, escaping, capability checks, or nonces
- designing only for one current use case if a shared abstraction is clearly needed

## First Implementation Milestones

If the plugin is scaffolded from scratch, the recommended build order is:

1. create the main plugin bootstrap file
2. add activation logic and schema versioning
3. define how `live` and `test` data are represented in the schema
4. create the required custom tables with `dbDelta()`
5. add repository classes for CRUD access
6. expose a minimal internal/public API for other plugins
7. add nightly external API sync for `live` data
8. add admin management pages
9. document usage in `README.md`

## Notes For Future Agents

- This plugin is intended as a shared master-data foundation.
- Favor stability and clarity over speed of implementation.
- If you introduce schema changes, update migrations and version handling together.
- If you introduce public helper functions or hooks, keep naming consistent and documented.
- Keep the distinction between `live` and `test` data explicit in both schema and code paths.
- On production, always assume the active dataset is `live`.
