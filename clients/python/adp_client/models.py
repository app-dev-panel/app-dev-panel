"""Data models for the ADP ingestion API."""

from __future__ import annotations

from dataclasses import dataclass, field, asdict
from typing import Any


@dataclass
class RequestInfo:
    method: str = "GET"
    uri: str = "/"
    headers: dict[str, str] = field(default_factory=dict)
    status_code: int | None = None
    duration: float | None = None

    def to_dict(self) -> dict[str, Any]:
        d: dict[str, Any] = {"method": self.method, "uri": self.uri}
        if self.headers:
            d["headers"] = self.headers
        if self.status_code is not None:
            d["statusCode"] = self.status_code
        if self.duration is not None:
            d["duration"] = self.duration
        return d


@dataclass
class DebugContext:
    type: str = "generic"
    language: str = "python"
    service: str = ""
    request: RequestInfo | None = None
    command: str | None = None
    environment: dict[str, str] = field(default_factory=dict)

    def to_dict(self) -> dict[str, Any]:
        d: dict[str, Any] = {"type": self.type, "language": self.language}
        if self.service:
            d["service"] = self.service
        if self.request is not None:
            d["request"] = self.request.to_dict()
        if self.command is not None:
            d["command"] = self.command
        if self.environment:
            d["environment"] = self.environment
        return d


@dataclass
class LogEntry:
    level: str
    message: str
    context: dict[str, Any] = field(default_factory=dict)
    line: str = ""
    service: str = ""

    def to_dict(self) -> dict[str, Any]:
        d: dict[str, Any] = {"level": self.level, "message": self.message}
        if self.context:
            d["context"] = self.context
        if self.line:
            d["line"] = self.line
        if self.service:
            d["service"] = self.service
        return d


@dataclass
class DebugEntry:
    collectors: dict[str, list[dict[str, Any]]]
    debug_id: str | None = None
    context: DebugContext | None = None
    summary: dict[str, Any] = field(default_factory=dict)

    def to_dict(self) -> dict[str, Any]:
        d: dict[str, Any] = {"collectors": self.collectors}
        if self.debug_id is not None:
            d["debugId"] = self.debug_id
        if self.context is not None:
            d["context"] = self.context.to_dict()
        if self.summary:
            d["summary"] = self.summary
        return d
