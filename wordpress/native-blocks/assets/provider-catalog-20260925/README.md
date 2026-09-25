# Provider catalog asset bundle

The 5,000 optimized logo files and archive metadata in this directory were generated from `provider-logos-5000.zip` with:

```powershell
python build_catalog.py "C:\path\to\provider-logos-5000.zip"
```

Requires Pillow with WebP support. The script reads the archive in place, validates all 5,000 catalog records and their source checksums, then writes 5,000 lossless WebP logos at no more than 240 × 140 pixels. It does not extract archive paths to disk.

The active `catalog.json` is produced by `node wordpress/native-blocks/merge-provider-catalog-20260925.mjs` after the initial archive conversion. It restores the 10 original SkySend categories and all 600 original entries, then selects 2,000 additional entries from the supplied archive. It is copied identically into the MU-plugin directory. The source archive's full 5,000-record catalog is pinned in Git commit `c1f2484` for reproducibility. Upload all files from `logos/` to `/wp-content/uploads/skysend-providers-20260925/`, keeping their four-digit filenames; 3,000 remain unused by the current catalog.

`attribution.json` stores archive metadata independently, indexed by the original four-digit `id`. `validation.json` records counts, dimensions, formats, and sizes. `checksums.sha256` records the initial conversion output, not the later merged active catalog; do not use its `catalog.json` line to verify the merged file. The source archive SHA-256 is retained in the merged catalog's `source.archive` and in `validation.json`.
