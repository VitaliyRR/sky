# Provider catalog asset bundle

This directory is generated from `provider-logos-5000.zip` with:

```powershell
python build_catalog.py "C:\path\to\provider-logos-5000.zip"
```

Requires Pillow with WebP support. The script reads the archive in place, validates all 5,000 catalog records and their source checksums, then writes 5,000 lossless WebP logos at no more than 240 × 140 pixels. It does not extract archive paths to disk.

`catalog.json` is the WordPress plugin data file. It has `version`, `source`, and 15 ordered `categories` keys. Each category contains its original Russian `label` and `items`. An item has `id` (`archive-0001`), `name`, and `logo` (an absolute path beneath `/wp-content/uploads/skysend-providers-20260925/`). Upload the files from `logos/` to that WordPress uploads directory, keeping the four-digit filenames.

`attribution.json` stores the source metadata independently, indexed by the original four-digit `id`. `validation.json` records counts, dimensions, formats, and sizes. `checksums.sha256` lists SHA-256 hashes for all generated logos and JSON files. The source archive SHA-256 is recorded in `catalog.json` and `validation.json`.
