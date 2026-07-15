#!/usr/bin/env python3
"""Create a transactionally consistent online backup of WordPress SQLite."""

from __future__ import annotations

import argparse
import json
import sqlite3
from pathlib import Path


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("source", type=Path)
    parser.add_argument("destination", type=Path)
    args = parser.parse_args()

    source = args.source.resolve(strict=True)
    destination = args.destination.resolve()
    destination.parent.mkdir(parents=True, exist_ok=True)
    if destination.exists():
        raise SystemExit(f"Refusing to overwrite existing backup: {destination}")

    with sqlite3.connect(f"file:{source}?mode=ro", uri=True) as source_db:
        with sqlite3.connect(destination) as backup_db:
            source_db.backup(backup_db)
            result = backup_db.execute("PRAGMA quick_check").fetchone()

    quick_check = result[0] if result else "no result"
    if quick_check != "ok":
        destination.unlink(missing_ok=True)
        raise SystemExit(f"Backup failed quick_check: {quick_check}")

    print(
        json.dumps(
            {
                "source": str(source),
                "destination": str(destination),
                "bytes": destination.stat().st_size,
                "quick_check": quick_check,
            }
        )
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
