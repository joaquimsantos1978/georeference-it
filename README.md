# georeference.it

A free, open-source crowdsourcing platform for georeferencing natural history specimen records from GBIF — and detecting errors in coordinates that already exist.

**Live platform:** [georeference.it](https://georeference.it)

---

## What it does

Only about 55% of natural history specimen records accessible via biodiversity aggregators such as GBIF are georeferenced, and only 31% include coordinate uncertainty information — both critical for rigorous ecological and evolutionary research (Marcer et al. 2021). georeference.it addresses this in three ways:

1. **Fill coordinate gaps** — volunteers georeference occurrence records grouped by locality description, following [Georeferencing Best Practices](https://docs.gbif.org/georeferencing-best-practices/1.0/en/) (Zermoglio et al. 2020)
2. **Validate by consensus** — community members agree or disagree with proposed georeferences; suggestions are validated once they reach a configurable confidence threshold
3. **Detect errors in existing data** — when occurrence records from the same locality cluster into spatially inconsistent groups, the platform flags and presents them as correction tasks

---

## Features

- Interactive map (Leaflet.js) with click-to-place point, draggable uncertainty circle, and Nominatim locality search
- Occurrence grouping by locality description with paginated left panel
- Agree/Disagree voting with point-weighted consensus model
- Automatic system suggestions for groups with existing but inconsistent coordinates (GBIF consistency check)
- Per-suggestion "correct georef. occurrences" opt-in — lets users explicitly correct existing GBIF coordinates
- Lazy-loaded occurrence lists per suggestion cluster
- Batch check/uncheck occurrences by institution
- Restores user's previous submission when revisiting a group
- User levels with weighted validation points
- Leaderboard, dashboard, and contribution history
- Public DarwinCore API (output: new georeferences, confirmations, corrections)
- Responsive UI, mobile-friendly
- Login with Google, GitHub, or ORCID — contributions are attributable to verified researcher identities
- Anonymous submissions supported

---

## How to use

1. Visit [georeference.it](https://georeference.it) — no account required to browse; create a free account to track contributions
2. Use the **Focus area** field to work on localities you know, or leave blank for any available task
3. Read the locality description, navigate the map, place a point and set the uncertainty radius
4. Uncheck any occurrences you believe belong elsewhere
5. Submit — or vote Agree/Disagree on existing suggestions

---

## Tech stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12 (PHP) |
| Database | MySQL |
| Frontend | Blade, Tailwind CSS, Alpine.js, Vite |
| Map | Leaflet.js |
| Geocoding | Nominatim (OpenStreetMap) |
| Auth | Laravel Breeze + Socialite (Google, GitHub, ORCID) |
| Data source | GBIF API + bulk DwCA import |

---

## Installation

### Requirements

- PHP 8.2+
- MySQL 8+
- Node.js 18+
- Composer

### Setup

```bash
git clone https://github.com/joaquimsantos1978/georeference-it.git
cd georeference-it
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

Configure your `.env`:

```env
DB_DATABASE=georeference_it
DB_USERNAME=your_user
DB_PASSWORD=your_password

GBIF_USERNAME=your_gbif_username
GBIF_PASSWORD=your_gbif_password
GBIF_EMAIL=your_email
```

Run migrations and seed:

```bash
php artisan migrate
php artisan db:seed
```

### Importing GBIF data

Request a GBIF occurrence download via the GBIF API (requires credentials in `.env`). Omit `--country` to download all specimens without coordinates globally:

```bash
php artisan gbif:request-download            # all countries
php artisan gbif:request-download --country=PT  # filter by country
```

Import the resulting download (you can also use a download key from a request made manually on gbif.org):

```bash
php artisan gbif:import-download {downloadKey}
```

Generate automatic system suggestions from existing georeferenced clusters. Omit `--country` to process all groups:

```bash
php artisan gbif:auto-suggest                # all groups
php artisan gbif:auto-suggest --country=PT  # limit by country
```

---

## Data output

The platform exposes a public API with three output types per occurrence record:

| Type | Description |
|---|---|
| New georeference | Coordinates for a previously uncoordinated record |
| Confirmation | Community agreement with existing GBIF coordinates |
| Correction | Alternative coordinates with rationale for a suspected error |

All output follows DarwinCore standards. API documentation: [georeference.it/api-docs](https://georeference.it/api-docs)

---

## Contributing

Pull requests welcome. Please open an issue first for significant changes.

---

## References

Marcer A, Haston E, Groom Q, et al. Quality issues in georeferencing: From physical collections to digital data repositories for ecological research. *Divers Distrib.* 2021;27:564–567. https://doi.org/10.1111/ddi.13208

Zermoglio PF, Chapman AD, Wieczorek JR, Luna MC, Bloom DA (2020) Georeferencing Quick Reference Guide. Copenhagen: GBIF Secretariat. https://doi.org/10.35035/e09p-h128

---

## Licence

MIT

---

## Citation

If you use georeference.it data in your research, please cite:

> Santos, J. (2026). georeference.it — Crowdsourced georeferencing and error detection for GBIF occurrence data. https://georeference.it
