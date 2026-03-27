package com.adp.jetbrains.client

import com.google.gson.JsonElement
import com.google.gson.JsonObject
import com.google.gson.annotations.SerializedName

/**
 * Data models mirroring ADP API response structures.
 *
 * These map to the JSON returned by:
 * - GET /debug/api (list) → DebugEntrySummary
 * - GET /debug/api/view/{id} → DebugEntryDetail
 * - Nested collector data → LogEntry, QueryEntry, EventEntry, etc.
 *
 * Source of truth: libs/API/CLAUDE.md and libs/Kernel/CLAUDE.md
 */

// ── Debug Entry Summary ────────────────────────────────────────────────
// Returned by GET /debug/api and GET /debug/api/summary/{id}

data class DebugEntrySummary(
    val id: String,
    val url: String? = null,
    val method: String? = null,
    val status: Int? = null,
    val timestamp: Double = 0.0,
    val collectors: Map<String, CollectorSummary>? = null,
    val tags: List<String>? = null,
)

/**
 * Per-collector summary data shown in the entries list.
 * Structure varies by collector — common fields extracted here.
 */
data class CollectorSummary(
    val total: Int? = null,
    val count: Int? = null,
    val error: Int? = null,
    val queries: QuerySummary? = null,
    val transactions: TransactionSummary? = null,
    val duplicateGroups: Int? = null,
    val hits: Int? = null,
    val misses: Int? = null,
    val totalOperations: Int? = null,
    val totalTime: Double? = null,
    val responseStatusCode: Int? = null,
)

data class QuerySummary(
    val total: Int = 0,
    val error: Int = 0,
)

data class TransactionSummary(
    val total: Int = 0,
)

// ── Debug Entry Detail ─────────────────────────────────────────────────
// Returned by GET /debug/api/view/{id}
// Contains full collector data keyed by collector FQCN.

data class DebugEntryDetail(
    val id: String? = null,
    val data: Map<String, JsonElement>? = null,
)

// ── Log Entry ──────────────────────────────────────────────────────────
// From LogCollector output

data class LogEntry(
    val level: String,           // PSR-3 level: emergency, alert, critical, error, warning, notice, info, debug
    val message: String,
    val context: JsonElement? = null,
    val line: String? = null,    // "file.php:42" format — source location of the log call
    val time: Double = 0.0,
)

// ── Query Entry ────────────────────────────────────────────────────────
// From DatabaseCollector output (QueryRecord)

data class QueryEntry(
    val sql: String,             // Parameterized SQL
    val rawSql: String? = null,  // SQL with params substituted
    val params: JsonElement? = null,
    val line: String? = null,    // "file.php:42" — source location of the query call
    val startTime: Double = 0.0,
    val endTime: Double = 0.0,
    val rowsNumber: Int? = null,
    val status: String? = null,  // ok, error
    val error: String? = null,
    val transactionId: String? = null,
    val actions: List<String>? = null,
) {
    /** Query duration in milliseconds */
    val durationMs: Double get() = (endTime - startTime) * 1000
}

// ── Event Entry ────────────────────────────────────────────────────────
// From EventCollector output

data class EventEntry(
    val name: String,            // Event class FQCN
    val event: JsonElement? = null,
    val file: String? = null,
    val line: Int? = null,
    val time: Double = 0.0,
)

// ── Exception Entry ────────────────────────────────────────────────────
// From ExceptionCollector output

data class ExceptionEntry(
    @SerializedName("class") val className: String,
    val message: String,
    val file: String? = null,
    val line: Int? = null,
    val code: Int? = null,
    val trace: List<TraceFrame>? = null,
    val traceAsString: String? = null,
)

data class TraceFrame(
    val file: String? = null,
    val line: Int? = null,
    @SerializedName("class") val className: String? = null,
    val function: String? = null,
    val type: String? = null,    // -> or ::
    val args: JsonElement? = null,
)

// ── HTTP Client Entry ──────────────────────────────────────────────────
// From HttpClientCollector output

data class HttpClientEntry(
    val method: String,
    val uri: String,
    val headers: JsonElement? = null,
    val startTime: Double = 0.0,
    val endTime: Double = 0.0,
    val totalTime: Double = 0.0,
    val responseStatus: Int? = null,
    val line: String? = null,
)

// ── Cache Entry ────────────────────────────────────────────────────────
// From CacheCollector output (CacheOperationRecord)

data class CacheEntry(
    val pool: String? = null,
    val operation: String,       // get, set, delete
    val key: String,
    val hit: Boolean = false,
    val duration: Double = 0.0,
    val value: JsonElement? = null,
)

// ── Service Call Entry ─────────────────────────────────────────────────
// From ServiceCollector output

data class ServiceCallEntry(
    val service: String,
    @SerializedName("class") val className: String? = null,
    val method: String,
    val arguments: JsonElement? = null,
    val result: JsonElement? = null,
    val status: String? = null,
    val error: String? = null,
    val timeStart: Double = 0.0,
    val timeEnd: Double = 0.0,
)

// ── Request/Response Entry ─────────────────────────────────────────────
// From RequestCollector output

data class RequestEntry(
    val requestUrl: String? = null,
    val requestPath: String? = null,
    val requestQuery: String? = null,
    val requestMethod: String? = null,
    val requestIsAjax: Boolean = false,
    val userIp: String? = null,
    val responseStatusCode: Int? = null,
    val request: JsonObject? = null,
    val response: JsonObject? = null,
)

// ── Middleware Entry ────────────────────────────────────────────────────
// From MiddlewareCollector output

data class MiddlewareEntry(
    val name: String,
    val time: Double = 0.0,
    val memory: Long = 0,
)

// ── OpenTelemetry Span ─────────────────────────────────────────────────
// From OpenTelemetryCollector output (SpanRecord)

data class OtelSpanEntry(
    val traceId: String,
    val spanId: String,
    val parentSpanId: String? = null,
    val operationName: String,
    val serviceName: String? = null,
    val startTime: Double = 0.0,
    val endTime: Double = 0.0,
    val duration: Double = 0.0,
    val status: String? = null,  // UNSET, OK, ERROR
    val kind: String? = null,    // INTERNAL, SERVER, CLIENT, PRODUCER, CONSUMER
    val attributes: JsonElement? = null,
    val events: JsonElement? = null,
)

// ── Deprecation Entry ──────────────────────────────────────────────────
// From DeprecationCollector output

data class DeprecationEntry(
    val message: String,
    val file: String? = null,
    val line: Int? = null,
    val trace: List<TraceFrame>? = null,
)

// ── VarDumper Entry ────────────────────────────────────────────────────
// From VarDumperCollector output

data class VarDumperEntry(
    val variable: JsonElement? = null,
    val line: String? = null,    // "file.php:42"
)

// ── Queue Message Entry ────────────────────────────────────────────────
// From QueueCollector output (MessageRecord)

data class QueueMessageEntry(
    val messageClass: String? = null,
    val bus: String? = null,
    val transport: String? = null,
    val dispatched: Boolean = false,
    val handled: Boolean = false,
    val failed: Boolean = false,
    val duration: Double = 0.0,
)

// ── Filesystem Entry ───────────────────────────────────────────────────
// From FilesystemStreamCollector output

data class FilesystemEntry(
    val operations: Map<String, List<FilesystemOperation>>? = null,
)

data class FilesystemOperation(
    val path: String? = null,
    val args: JsonElement? = null,
)

// ── Template Entry ─────────────────────────────────────────────────────
// From TemplateCollector output

data class TemplateEntry(
    val template: String,
    val renderTime: Double = 0.0,
)

// ── Helpers ────────────────────────────────────────────────────────────

/**
 * Parse "file.php:42" format into (filePath, lineNumber).
 * ADP stores source locations in this format for logs, queries, dumps, etc.
 */
fun parseFileLine(line: String?): Pair<String, Int>? {
    if (line == null) return null
    val lastColon = line.lastIndexOf(':')
    if (lastColon <= 0) return null
    val file = line.substring(0, lastColon)
    val lineNum = line.substring(lastColon + 1).toIntOrNull() ?: return null
    return Pair(file, lineNum)
}
