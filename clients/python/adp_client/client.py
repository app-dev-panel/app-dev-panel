"""HTTP client for the ADP debug ingestion API."""

from __future__ import annotations

import json
import time
import urllib.request
from typing import Any

from adp_client.models import DebugContext, DebugEntry, LogEntry


class ADPClient:
    """Client for sending debug data to App Dev Panel.

    Usage:
        client = ADPClient("http://localhost:8080")

        # Send a full debug entry
        client.ingest(DebugEntry(
            collectors={
                "logs": [{"time": time.time(), "level": "info", "message": "Hello"}],
                "http_client": [{"method": "GET", "uri": "/api", "totalTime": 0.5}],
            },
            context=DebugContext(service="my-app"),
        ))

        # Send a single log (shorthand)
        client.log("error", "Something went wrong", line="app.py:42")
    """

    def __init__(self, base_url: str = "http://localhost:8080") -> None:
        self.base_url = base_url.rstrip("/")

    def ingest(self, entry: DebugEntry) -> dict[str, Any]:
        """Submit a single debug entry."""
        return self._post("/debug/api/ingest", entry.to_dict())

    def ingest_batch(self, entries: list[DebugEntry]) -> dict[str, Any]:
        """Submit multiple debug entries."""
        return self._post("/debug/api/ingest/batch", {
            "entries": [e.to_dict() for e in entries],
        })

    def log(
        self,
        level: str,
        message: str,
        *,
        context: dict[str, Any] | None = None,
        line: str = "",
        service: str = "",
    ) -> dict[str, Any]:
        """Submit a single log message (convenience method)."""
        entry = LogEntry(
            level=level,
            message=message,
            context=context or {},
            line=line,
            service=service,
        )
        return self._post("/debug/api/ingest/log", entry.to_dict())

    def get_openapi_spec(self) -> dict[str, Any]:
        """Fetch the OpenAPI specification from the server."""
        url = f"{self.base_url}/debug/api/openapi.json"
        req = urllib.request.Request(url)
        with urllib.request.urlopen(req, timeout=10) as resp:
            return json.loads(resp.read())

    def _post(self, path: str, body: dict[str, Any]) -> dict[str, Any]:
        url = f"{self.base_url}{path}"
        data = json.dumps(body).encode("utf-8")
        req = urllib.request.Request(
            url,
            data=data,
            headers={"Content-Type": "application/json"},
            method="POST",
        )
        with urllib.request.urlopen(req, timeout=30) as resp:
            return json.loads(resp.read())
