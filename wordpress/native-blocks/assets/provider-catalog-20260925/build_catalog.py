"""Build the SkySend provider logo asset catalog from provider-logos-5000.zip.

Usage: python build_catalog.py path/to/provider-logos-5000.zip

The archive is read in place. No archive member is extracted by filename. The
output files are written beside this script, so the input archive stays intact.
Requires Pillow with WebP support.
"""

from __future__ import annotations

import argparse
import hashlib
import io
import json
import re
import sys
import zipfile
from collections import Counter
from pathlib import Path, PurePosixPath

from PIL import Image, ImageOps, features


OUTPUT = Path(__file__).resolve().parent
MAX_SIZE = (240, 140)
EXPECTED_COUNT = 5000
EXPECTED_CATEGORIES = 15
ALLOWED_EXTENSIONS = {".png", ".jpg", ".jpeg", ".gif", ".webp"}
SOURCE_JSON_FILES = {"catalog.json", "attribution.json", "summary.json"}
UPLOAD_PATH = "/wp-content/uploads/skysend-providers-20260925"
CATEGORY_SLUGS = (
    "education-science",
    "media-digital",
    "manufacturing-goods",
    "other-organizations-services",
    "retail-food-hospitality",
    "medicine-health",
    "banking-payments-insurance",
    "transport-logistics",
    "government-services",
    "culture-leisure",
    "civic-organizations",
    "utilities-energy",
    "communications-internet",
    "digital-services",
    "web-development-digital-services",
)


def sha256(data: bytes) -> str:
    return hashlib.sha256(data).hexdigest()


def json_bytes(value: object) -> bytes:
    return (json.dumps(value, ensure_ascii=False, indent=2) + "\n").encode("utf-8")


def validate_member_name(name: str) -> None:
    path = PurePosixPath(name)
    if (
        not name
        or "\\" in name
        or name.startswith("/")
        or re.match(r"^[A-Za-z]:", name)
        or any(part in {"", ".", ".."} for part in name.split("/"))
        or str(path) != name
    ):
        raise ValueError(f"Unsafe archive member: {name!r}")


def read_json(archive: zipfile.ZipFile, name: str) -> object:
    return json.loads(archive.read(name).decode("utf-8-sig"))


def check_source(archive: zipfile.ZipFile) -> tuple[list[dict], list[dict], dict]:
    members = archive.infolist()
    names = [item.filename for item in members]
    for name in names:
        validate_member_name(name)
    if len(names) != len(set(names)):
        raise ValueError("Duplicate archive member names")
    if any(item.is_dir() or item.flag_bits & 1 for item in members):
        raise ValueError("Archive contains a directory or encrypted member")
    if not SOURCE_JSON_FILES.issubset(names):
        raise ValueError("Archive is missing a required JSON file")

    catalog = read_json(archive, "catalog.json")
    attribution = read_json(archive, "attribution.json")
    summary = read_json(archive, "summary.json")
    if not isinstance(catalog, list) or len(catalog) != EXPECTED_COUNT:
        raise ValueError("Source catalog must contain exactly 5,000 entries")
    if not isinstance(attribution, list) or len(attribution) != EXPECTED_COUNT:
        raise ValueError("Source attribution must contain exactly 5,000 entries")
    if not isinstance(summary, dict):
        raise ValueError("Source summary is not an object")

    ids: set[str] = set()
    logos: set[str] = set()
    categories: set[str] = set()
    for row in catalog:
        if not isinstance(row, dict):
            raise ValueError("Invalid catalog record")
        identifier = row.get("id")
        name = row.get("name")
        category = row.get("category")
        logo = row.get("logo_file")
        if (
            not isinstance(identifier, str)
            or not re.fullmatch(r"\d{4}", identifier)
            or not isinstance(name, str)
            or not name.strip()
            or not isinstance(category, str)
            or not category.strip()
            or not isinstance(logo, str)
        ):
            raise ValueError(f"Invalid catalog fields: {identifier!r}")
        validate_member_name(logo)
        if not logo.startswith("logos/") or PurePosixPath(logo).suffix.lower() not in ALLOWED_EXTENSIONS:
            raise ValueError(f"Invalid logo path: {logo!r}")
        if identifier in ids or logo in logos:
            raise ValueError(f"Duplicate id or logo: {identifier!r}")
        ids.add(identifier)
        logos.add(logo)
        categories.add(category)

    if len(categories) != EXPECTED_CATEGORIES:
        raise ValueError(f"Expected 15 categories, found {len(categories)}")
    archive_logos = {name for name in names if name.startswith("logos/")}
    if logos != archive_logos:
        raise ValueError(f"Catalog/archive logo mismatch: missing={len(logos - archive_logos)}, extra={len(archive_logos - logos)}")
    attr_ids = [row.get("id") if isinstance(row, dict) else None for row in attribution]
    if len(set(attr_ids)) != EXPECTED_COUNT or set(attr_ids) != ids:
        raise ValueError("Attribution ids do not match catalog ids")
    return catalog, attribution, summary


def convert_logo(source: bytes, filename: str) -> tuple[bytes, dict]:
    # Pillow rejects exceptionally large decompression bombs. The archive is
    # untrusted input even though the expected number of files is known.
    with Image.open(io.BytesIO(source)) as original:
        original.load()
        source_size = list(original.size)
        source_format = original.format
        image = ImageOps.exif_transpose(original)
        if image.mode in {"RGBA", "LA", "P"} or "transparency" in image.info:
            image = image.convert("RGBA")
        else:
            image = image.convert("RGB")
        image.thumbnail(MAX_SIZE, Image.Resampling.LANCZOS)
        buffer = io.BytesIO()
        image.save(buffer, "WEBP", lossless=True, method=4, exact=True)
        output = buffer.getvalue()
        if not output:
            raise ValueError(f"Empty WebP output for {filename}")
        # Decode the result as a final format/dimension validation.
        with Image.open(io.BytesIO(output)) as check:
            check.load()
            if check.format != "WEBP" or check.width > MAX_SIZE[0] or check.height > MAX_SIZE[1]:
                raise ValueError(f"Invalid optimized logo: {filename}")
        return output, {
            "source_format": source_format,
            "source_dimensions": source_size,
            "output_dimensions": list(image.size),
        }


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("archive", type=Path, help="Source provider-logos-5000.zip")
    args = parser.parse_args()
    if not features.check("webp"):
        raise RuntimeError("This Pillow build does not support WebP")
    archive_path = args.archive.resolve(strict=True)
    archive_hash = hashlib.sha256()
    with archive_path.open("rb") as file:
        for chunk in iter(lambda: file.read(1024 * 1024), b""):
            archive_hash.update(chunk)

    # Validate all references before creating output files.
    with zipfile.ZipFile(archive_path) as archive:
        catalog, attribution, source_summary = check_source(archive)
        attribution_by_id = {row["id"]: row for row in attribution}
        logo_dir = OUTPUT / "logos"
        logo_dir.mkdir(parents=True, exist_ok=True)
        providers = []
        checksum_lines = []
        source_formats: Counter[str] = Counter()
        category_counts: Counter[str] = Counter()
        source_bytes = 0
        output_bytes = 0
        largest_source_dimensions = [0, 0]

        for index, row in enumerate(catalog, start=1):
            source = archive.read(row["logo_file"])
            expected_hash = attribution_by_id[row["id"]].get("sha256")
            if expected_hash != sha256(source):
                raise ValueError(f"Source logo checksum mismatch: {row['logo_file']}")
            converted, details = convert_logo(source, row["logo_file"])
            output_name = f"{row['id']}.webp"
            target = logo_dir / output_name
            target.write_bytes(converted)
            checksum_lines.append(f"{sha256(converted)}  logos/{output_name}")
            providers.append({
                "id": row["id"],
                "name": row["name"],
                "category": row["category"],
                "logo_file": f"logos/{output_name}",
            })
            source_formats[str(details["source_format"])] += 1
            category_counts[row["category"]] += 1
            source_bytes += len(source)
            output_bytes += len(converted)
            largest_source_dimensions = [
                max(largest_source_dimensions[0], details["source_dimensions"][0]),
                max(largest_source_dimensions[1], details["source_dimensions"][1]),
            ]
            if index % 500 == 0:
                print(f"Converted {index:,}/{EXPECTED_COUNT:,}", flush=True)

    # Category order follows first appearance in the source catalog.
    categories = list(dict.fromkeys(row["category"] for row in catalog))
    grouped = {
        slug: {"label": label, "items": []}
        for slug, label in zip(CATEGORY_SLUGS, categories, strict=True)
    }
    slug_by_label = dict(zip(categories, CATEGORY_SLUGS, strict=True))
    for provider in providers:
        grouped[slug_by_label[provider["category"]]]["items"].append({
            "id": f"archive-{provider['id']}",
            "name": provider["name"],
            "logo": f"{UPLOAD_PATH}/{provider['id']}.webp",
        })
    normalized = {
        "version": "2026-09-25",
        "source": {
            "archive": archive_path.name,
            "sha256": archive_hash.hexdigest(),
        },
        "categories": grouped,
    }
    report = {
        "source_archive": archive_path.name,
        "source_archive_sha256": archive_hash.hexdigest(),
        "source_summary_created_at": source_summary.get("created_at"),
        "provider_count": len(providers),
        "category_count": len(categories),
        "category_counts": dict(category_counts),
        "source_formats": dict(source_formats),
        "source_logo_bytes": source_bytes,
        "optimized_logo_bytes": output_bytes,
        "largest_source_dimensions": largest_source_dimensions,
        "max_output_dimensions": list(MAX_SIZE),
        "output_format": "WebP lossless",
        "pillow_version": Image.__version__,
        "missing_logos": 0,
        "attribution_count": len(attribution),
    }
    for filename, value in (
        ("catalog.json", normalized),
        ("attribution.json", attribution),
        ("validation.json", report),
    ):
        content = json_bytes(value)
        (OUTPUT / filename).write_bytes(content)
        checksum_lines.append(f"{sha256(content)}  {filename}")
    (OUTPUT / "checksums.sha256").write_text("\n".join(checksum_lines) + "\n", encoding="ascii")
    print(json.dumps(report, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    try:
        main()
    except (OSError, ValueError, zipfile.BadZipFile) as exc:
        print(f"Catalog build failed: {exc}", file=sys.stderr)
        raise SystemExit(1) from exc
