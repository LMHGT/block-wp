#!/usr/bin/env python3
"""Capture route-level public metadata for a WordPress handoff baseline."""

from __future__ import annotations

import argparse
import datetime as dt
import json
import re
import urllib.request
import xml.etree.ElementTree as ET
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urljoin


class MetadataParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__(convert_charrefs=True)
        self.in_title = False
        self.title_parts: list[str] = []
        self.meta: list[dict[str, str]] = []
        self.canonicals: list[str] = []
        self.json_ld: list[str] = []
        self._json_ld_buffer: list[str] | None = None

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        values = {key.lower(): value or "" for key, value in attrs}
        if tag.lower() == "title":
            self.in_title = True
        elif tag.lower() == "meta":
            self.meta.append(values)
        elif tag.lower() == "link" and values.get("rel", "").lower() == "canonical":
            self.canonicals.append(values.get("href", ""))
        elif tag.lower() == "script" and values.get("type", "").lower() == "application/ld+json":
            self._json_ld_buffer = []

    def handle_endtag(self, tag: str) -> None:
        if tag.lower() == "title":
            self.in_title = False
        elif tag.lower() == "script" and self._json_ld_buffer is not None:
            self.json_ld.append("".join(self._json_ld_buffer))
            self._json_ld_buffer = None

    def handle_data(self, data: str) -> None:
        if self.in_title:
            self.title_parts.append(data)
        if self._json_ld_buffer is not None:
            self._json_ld_buffer.append(data)


def first_meta(meta: list[dict[str, str]], key: str, value: str) -> str:
    for item in meta:
        if item.get(key, "").lower() == value.lower():
            return item.get("content", "")
    return ""


def json_ld_types(documents: list[str]) -> list[str]:
    found: set[str] = set()
    for raw in documents:
        try:
            decoded = json.loads(raw)
        except json.JSONDecodeError:
            found.add("INVALID_JSON_LD")
            continue
        nodes = decoded.get("@graph", []) if isinstance(decoded, dict) else decoded
        if not isinstance(nodes, list):
            nodes = [decoded]
        for node in nodes:
            if not isinstance(node, dict):
                continue
            types = node.get("@type", [])
            if not isinstance(types, list):
                types = [types]
            found.update(str(value) for value in types if value)
    return sorted(found)


def fetch(url: str) -> tuple[int, str, str, dict[str, str]]:
    request = urllib.request.Request(url, headers={"User-Agent": "LMHG-Metadata-Baseline/1.0"})
    with urllib.request.urlopen(request, timeout=30) as response:
        body = response.read().decode("utf-8", errors="replace")
        return response.status, response.geturl(), body, {key.lower(): value for key, value in response.headers.items()}


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("base_url")
    parser.add_argument("output", type=Path)
    args = parser.parse_args()

    base_url = args.base_url.rstrip("/") + "/"
    output = args.output.resolve()
    if output.exists():
        raise SystemExit(f"Refusing to overwrite existing baseline: {output}")

    _, _, sitemap_xml, _ = fetch(urljoin(base_url, "wp-sitemap-posts-page-1.xml"))
    root = ET.fromstring(sitemap_xml)
    urls = [element.text for element in root.iter() if element.tag.endswith("loc") and element.text]
    routes: list[dict[str, object]] = []
    for url in urls:
        status, effective_url, html, headers = fetch(url)
        parsed = MetadataParser()
        parsed.feed(html)
        routes.append(
            {
                "url": url,
                "effective_url": effective_url,
                "status": status,
                "title": " ".join("".join(parsed.title_parts).split()),
                "description": first_meta(parsed.meta, "name", "description"),
                "canonical": parsed.canonicals,
                "robots_meta": first_meta(parsed.meta, "name", "robots"),
                "x_robots_tag": headers.get("x-robots-tag", ""),
                "og_title": first_meta(parsed.meta, "property", "og:title"),
                "og_description": first_meta(parsed.meta, "property", "og:description"),
                "og_url": first_meta(parsed.meta, "property", "og:url"),
                "twitter_card": first_meta(parsed.meta, "name", "twitter:card"),
                "json_ld_documents": len(parsed.json_ld),
                "json_ld_types": json_ld_types(parsed.json_ld),
                "raw_lmhg_shortcodes": len(re.findall(r"\[lmhg_[^\]]+\]", html)),
            }
        )

    payload = {
        "captured_at": dt.datetime.now(dt.timezone.utc).isoformat(),
        "base_url": base_url,
        "route_count": len(routes),
        "routes": routes,
    }
    output.parent.mkdir(parents=True, exist_ok=True)
    output.write_text(json.dumps(payload, indent=2, sort_keys=True) + "\n", encoding="utf-8")
    print(json.dumps({"output": str(output), "route_count": len(routes)}))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
