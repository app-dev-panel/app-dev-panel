package com.adp.jetbrains.client

import com.google.gson.Gson
import com.google.gson.reflect.TypeToken
import java.util.concurrent.ConcurrentHashMap

/**
 * In-memory cache of ADP debug data, indexed by file path and line number.
 *
 * This is the bridge between the API client and IDE features (gutter icons,
 * inlay hints, inspections). When new debug entries arrive via SSE, the cache
 * is rebuilt so that IDE components can quickly look up "what happened at this
 * file:line?" without making HTTP requests on every editor repaint.
 *
 * Index structure:
 *   filePath → lineNumber → list of SourceAnnotation
 *
 * Each SourceAnnotation represents one piece of debug data tied to a source location:
 * a log message, SQL query, event dispatch, exception, etc.
 */
class AdpDataCache {

    private val gson = Gson()

    /** All debug entry summaries, most recent first */
    @Volatile
    var entries: List<DebugEntrySummary> = emptyList()
        private set

    /** Cached full details by entry ID */
    private val detailCache = ConcurrentHashMap<String, DebugEntryDetail>()

    /**
     * File-level index: filePath → lineNumber → annotations.
     * Rebuilt when the latest debug entry detail is loaded.
     */
    private val fileIndex = ConcurrentHashMap<String, MutableMap<Int, MutableList<SourceAnnotation>>>()

    /** The ID of the entry currently indexed in fileIndex */
    @Volatile
    var indexedEntryId: String? = null
        private set

    fun updateEntries(newEntries: List<DebugEntrySummary>) {
        entries = newEntries

        // Auto-fetch detail for the latest entry to populate file index
        if (newEntries.isNotEmpty()) {
            val latestId = newEntries.first().id
            if (latestId != indexedEntryId && !detailCache.containsKey(latestId)) {
                AdpApiClient.getInstance().fetchEntryDetail(latestId) { detail ->
                    rebuildFileIndex(latestId, detail)
                }
            }
        }
    }

    fun cacheDetail(id: String, detail: DebugEntryDetail) {
        detailCache[id] = detail
        // Index the most recent entry for gutter/inlay display
        if (entries.isNotEmpty() && entries.first().id == id) {
            rebuildFileIndex(id, detail)
        }
    }

    fun getDetail(id: String): DebugEntryDetail? = detailCache[id]

    /**
     * Get all annotations for a specific file.
     * Returns map of lineNumber → annotations.
     * Used by GutterIconProvider and InlayHintsProvider for the active editor file.
     */
    fun getAnnotationsForFile(filePath: String): Map<Int, List<SourceAnnotation>> {
        return fileIndex[normalizePath(filePath)] ?: emptyMap()
    }

    /**
     * Get annotations at a specific file:line.
     * Used by inspections and quick-info popups.
     */
    fun getAnnotationsAt(filePath: String, line: Int): List<SourceAnnotation> {
        return fileIndex[normalizePath(filePath)]?.get(line) ?: emptyList()
    }

    /**
     * Rebuild the file-level index from a debug entry detail.
     *
     * Parses all collector data, extracts file:line references,
     * and builds the lookup index used by gutter icons and inlay hints.
     */
    private fun rebuildFileIndex(entryId: String, detail: DebugEntryDetail) {
        val newIndex = ConcurrentHashMap<String, MutableMap<Int, MutableList<SourceAnnotation>>>()

        detail.data?.forEach { (collectorKey, data) ->
            when {
                // LogCollector — array of log entries with `line` field ("file.php:42")
                collectorKey.contains("LogCollector") -> {
                    val logs: List<LogEntry> = tryParse(data) ?: return@forEach
                    logs.forEach { log ->
                        val (file, line) = parseFileLine(log.line) ?: return@forEach
                        addAnnotation(newIndex, file, line, SourceAnnotation(
                            type = AnnotationType.LOG,
                            level = log.level,
                            message = log.message,
                            entryId = entryId,
                        ))
                    }
                }

                // DatabaseCollector — queries with `line` field
                collectorKey.contains("DatabaseCollector") -> {
                    val queries: List<QueryEntry> = tryParse(data) ?: return@forEach
                    queries.forEach { query ->
                        val (file, line) = parseFileLine(query.line) ?: return@forEach
                        addAnnotation(newIndex, file, line, SourceAnnotation(
                            type = AnnotationType.QUERY,
                            sql = query.sql,
                            durationMs = query.durationMs,
                            queryStatus = query.status,
                            entryId = entryId,
                        ))
                    }
                }

                // ExceptionCollector — exceptions with `file` and `line` fields
                collectorKey.contains("ExceptionCollector") -> {
                    val exceptions: List<ExceptionEntry> = tryParse(data) ?: return@forEach
                    exceptions.forEach { ex ->
                        if (ex.file != null && ex.line != null) {
                            addAnnotation(newIndex, ex.file, ex.line, SourceAnnotation(
                                type = AnnotationType.EXCEPTION,
                                message = "${ex.className}: ${ex.message}",
                                level = "error",
                                entryId = entryId,
                            ))
                        }
                        // Also index each stack trace frame
                        ex.trace?.forEach { frame ->
                            if (frame.file != null && frame.line != null) {
                                addAnnotation(newIndex, frame.file, frame.line, SourceAnnotation(
                                    type = AnnotationType.EXCEPTION_TRACE,
                                    message = "${ex.className}: ${ex.message}",
                                    level = "error",
                                    entryId = entryId,
                                ))
                            }
                        }
                    }
                }

                // EventCollector — events with `file` and `line`
                collectorKey.contains("EventCollector") -> {
                    val events: List<EventEntry> = tryParse(data) ?: return@forEach
                    events.forEach { event ->
                        if (event.file != null && event.line != null) {
                            addAnnotation(newIndex, event.file, event.line, SourceAnnotation(
                                type = AnnotationType.EVENT,
                                message = event.name,
                                entryId = entryId,
                            ))
                        }
                    }
                }

                // HttpClientCollector — HTTP requests with `line`
                collectorKey.contains("HttpClientCollector") -> {
                    val requests: List<HttpClientEntry> = tryParse(data) ?: return@forEach
                    requests.forEach { req ->
                        val (file, line) = parseFileLine(req.line) ?: return@forEach
                        addAnnotation(newIndex, file, line, SourceAnnotation(
                            type = AnnotationType.HTTP_REQUEST,
                            message = "${req.method} ${req.uri}",
                            durationMs = req.totalTime * 1000,
                            level = if (req.responseStatus in 400..599) "error" else "info",
                            entryId = entryId,
                        ))
                    }
                }

                // CacheCollector — cache operations (no direct file:line, skip for now)
                collectorKey.contains("CacheCollector") -> {
                    // Cache operations don't have file:line in current ADP version.
                    // Could be added in future via proxy backtrace capture.
                }

                // VarDumperCollector — dumps with `line`
                collectorKey.contains("VarDumperCollector") -> {
                    val dumps: List<VarDumperEntry> = tryParse(data) ?: return@forEach
                    dumps.forEach { dump ->
                        val (file, line) = parseFileLine(dump.line) ?: return@forEach
                        addAnnotation(newIndex, file, line, SourceAnnotation(
                            type = AnnotationType.DUMP,
                            message = "dump()",
                            entryId = entryId,
                        ))
                    }
                }

                // DeprecationCollector — deprecation warnings with file:line
                collectorKey.contains("DeprecationCollector") -> {
                    val deprecations: List<DeprecationEntry> = tryParse(data) ?: return@forEach
                    deprecations.forEach { dep ->
                        if (dep.file != null && dep.line != null) {
                            addAnnotation(newIndex, dep.file, dep.line, SourceAnnotation(
                                type = AnnotationType.DEPRECATION,
                                message = dep.message,
                                level = "warning",
                                entryId = entryId,
                            ))
                        }
                    }
                }
            }
        }

        fileIndex.clear()
        fileIndex.putAll(newIndex)
        indexedEntryId = entryId
    }

    private fun addAnnotation(
        index: ConcurrentHashMap<String, MutableMap<Int, MutableList<SourceAnnotation>>>,
        file: String,
        line: Int,
        annotation: SourceAnnotation,
    ) {
        val normalizedFile = normalizePath(file)
        index.getOrPut(normalizedFile) { ConcurrentHashMap() }
            .getOrPut(line) { mutableListOf() }
            .add(annotation)
    }

    private fun normalizePath(path: String): String {
        // Apply path mapping from settings (remote → local)
        val mappings = com.adp.jetbrains.settings.AdpSettingsState.getInstance().pathMappings
        var result = path
        for ((remote, local) in mappings) {
            if (result.startsWith(remote)) {
                result = local + result.removePrefix(remote)
                break
            }
        }
        return result.replace("\\", "/")
    }

    private inline fun <reified T> tryParse(data: com.google.gson.JsonElement): T? {
        return try {
            val type = object : TypeToken<T>() {}.type
            gson.fromJson(data, type)
        } catch (_: Exception) {
            null
        }
    }
}

/**
 * A single annotation tied to a source file location.
 * Represents one piece of debug data (log, query, exception, etc.) at a specific line.
 */
data class SourceAnnotation(
    val type: AnnotationType,
    val message: String? = null,
    val level: String? = null,       // PSR-3 level or "error"/"warning"/"info"
    val sql: String? = null,         // For QUERY type
    val durationMs: Double? = null,  // For QUERY and HTTP_REQUEST types
    val queryStatus: String? = null, // "ok" or "error"
    val entryId: String,             // Debug entry this belongs to
)

enum class AnnotationType {
    LOG,
    QUERY,
    EXCEPTION,
    EXCEPTION_TRACE,
    EVENT,
    HTTP_REQUEST,
    DUMP,
    DEPRECATION,
    CACHE,
    SERVICE_CALL,
}
