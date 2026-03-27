package com.adp.jetbrains.client

import com.adp.jetbrains.settings.AdpSettingsState
import com.google.gson.Gson
import com.google.gson.JsonElement
import com.google.gson.JsonObject
import com.google.gson.reflect.TypeToken
import com.intellij.openapi.Disposable
import com.intellij.openapi.application.ApplicationManager
import com.intellij.openapi.components.Service
import com.intellij.openapi.diagnostic.Logger
import okhttp3.*
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.RequestBody.Companion.toRequestBody
import okhttp3.sse.EventSource
import okhttp3.sse.EventSourceListener
import okhttp3.sse.EventSources
import java.io.IOException
import java.util.concurrent.CopyOnWriteArrayList
import java.util.concurrent.TimeUnit

/**
 * ADP API Client — central HTTP + SSE client for communicating with ADP backend.
 *
 * Connects to the ADP REST API (`/debug/api/*`, `/inspect/api/*`) and the
 * SSE event stream (`/debug/api/event-stream`) to provide real-time debug data
 * to all plugin components.
 *
 * Architecture:
 * - Singleton application service (shared across all projects)
 * - OkHttp for HTTP requests and SSE
 * - Gson for JSON serialization
 * - Listener pattern for SSE events (tool window, notifications, status bar subscribe)
 *
 * Data flow:
 *   ADP Backend ──HTTP/SSE──► AdpApiClient ──listeners──► ToolWindow, StatusBar, Notifications
 *                                  │
 *                                  ├──► AdpDataCache (in-memory last N entries)
 *                                  └──► GutterIconProvider, InlayHintsProvider (read cache)
 */
@Service(Service.Level.APP)
class AdpApiClient : Disposable {

    private val log = Logger.getInstance(AdpApiClient::class.java)
    private val gson = Gson()
    private val listeners = CopyOnWriteArrayList<AdpEventListener>()

    private val httpClient = OkHttpClient.Builder()
        .connectTimeout(5, TimeUnit.SECONDS)
        .readTimeout(30, TimeUnit.SECONDS)
        .writeTimeout(10, TimeUnit.SECONDS)
        .build()

    private val sseClient = OkHttpClient.Builder()
        .connectTimeout(5, TimeUnit.SECONDS)
        .readTimeout(0, TimeUnit.MINUTES) // SSE streams are long-lived
        .build()

    private var eventSource: EventSource? = null
    private var connected = false

    val dataCache = AdpDataCache()

    // ── Connection Management ──────────────────────────────────────────

    /**
     * Connect to ADP SSE event stream. Called on settings change or manual reconnect.
     * Automatically fetches initial entries, then subscribes to real-time updates.
     */
    fun connect() {
        disconnect()

        val baseUrl = AdpSettingsState.getInstance().baseUrl.trimEnd('/')
        if (baseUrl.isBlank()) {
            log.warn("ADP base URL is not configured")
            return
        }

        // Fetch initial entries
        fetchEntries()

        // Start SSE stream
        val request = Request.Builder()
            .url("$baseUrl/debug/api/event-stream")
            .header("Accept", "text/event-stream")
            .build()

        val factory = EventSources.createFactory(sseClient)
        eventSource = factory.newEventSource(request, object : EventSourceListener() {
            override fun onOpen(eventSource: EventSource, response: Response) {
                connected = true
                log.info("ADP SSE connected to $baseUrl")
                listeners.forEach { it.onConnectionChanged(true) }
            }

            override fun onEvent(eventSource: EventSource, id: String?, type: String?, data: String) {
                handleSseEvent(type, data)
            }

            override fun onFailure(eventSource: EventSource, t: Throwable?, response: Response?) {
                connected = false
                log.warn("ADP SSE connection failed: ${t?.message}")
                listeners.forEach { it.onConnectionChanged(false) }

                // Auto-reconnect after 5 seconds
                ApplicationManager.getApplication().executeOnPooledThread {
                    Thread.sleep(5000)
                    if (!connected) connect()
                }
            }

            override fun onClosed(eventSource: EventSource) {
                connected = false
                listeners.forEach { it.onConnectionChanged(false) }
            }
        })
    }

    fun disconnect() {
        eventSource?.cancel()
        eventSource = null
        connected = false
    }

    fun isConnected(): Boolean = connected

    // ── Debug API ──────────────────────────────────────────────────────

    /**
     * GET /debug/api — List all debug entries (summaries).
     * Returns list of DebugEntrySummary with request URL, method, status, timestamps,
     * and per-collector summary counts (log total, query count, etc.)
     */
    fun fetchEntries(callback: ((List<DebugEntrySummary>) -> Unit)? = null) {
        get("/debug/api") { json ->
            val entries = parseEntriesList(json)
            dataCache.updateEntries(entries)
            callback?.invoke(entries)
            listeners.forEach { it.onEntriesUpdated(entries) }
        }
    }

    /**
     * GET /debug/api/view/{id} — Full collector data for a single debug entry.
     * Returns all collector outputs keyed by collector FQCN. Each collector provides
     * its own data structure (logs array, queries array, events array, etc.)
     */
    fun fetchEntryDetail(id: String, callback: (DebugEntryDetail) -> Unit) {
        get("/debug/api/view/$id") { json ->
            val detail = gson.fromJson(json, DebugEntryDetail::class.java)
            dataCache.cacheDetail(id, detail)
            callback(detail)
        }
    }

    /**
     * GET /debug/api/summary/{id} — Single entry summary metadata.
     */
    fun fetchEntrySummary(id: String, callback: (DebugEntrySummary) -> Unit) {
        get("/debug/api/summary/$id") { json ->
            val summary = gson.fromJson(json, DebugEntrySummary::class.java)
            callback(summary)
        }
    }

    // ── Inspector API ──────────────────────────────────────────────────

    /**
     * GET /inspect/api/routes — All registered routes.
     * Used for route name autocompletion and validation inspections.
     */
    fun fetchRoutes(callback: (JsonElement) -> Unit) {
        get("/inspect/api/routes", callback)
    }

    /**
     * GET /inspect/api/events — Event listener map.
     * Used for "Find Listeners" navigation from event dispatch calls.
     */
    fun fetchEvents(callback: (JsonElement) -> Unit) {
        get("/inspect/api/events", callback)
    }

    /**
     * GET /inspect/api/config — DI container configuration.
     * Used for service name autocompletion in container->get() calls.
     */
    fun fetchContainerConfig(callback: (JsonElement) -> Unit) {
        get("/inspect/api/config", callback)
    }

    /**
     * GET /inspect/api/translations — Translation catalogs.
     * Used for translation key autocompletion.
     */
    fun fetchTranslations(callback: (JsonElement) -> Unit) {
        get("/inspect/api/translations", callback)
    }

    /**
     * POST /inspect/api/table/explain — EXPLAIN a SQL query.
     * Used in the "Explain Query" quick-fix action.
     */
    fun explainQuery(sql: String, callback: (JsonElement) -> Unit) {
        val body = gson.toJson(mapOf("sql" to sql))
        post("/inspect/api/table/explain", body, callback)
    }

    /**
     * PUT /inspect/api/request — Replay an HTTP request.
     * Re-executes a previously captured request and returns the new response.
     */
    fun replayRequest(requestData: JsonObject, callback: (JsonElement) -> Unit) {
        put("/inspect/api/request", requestData.toString(), callback)
    }

    /**
     * POST /inspect/api/curl/build — Build a cURL command from request data.
     */
    fun buildCurl(requestData: JsonObject, callback: (String) -> Unit) {
        post("/inspect/api/curl/build", requestData.toString()) { json ->
            val curl = json.asJsonObject.get("data")?.asString ?: ""
            callback(curl)
        }
    }

    // ── Listener Management ────────────────────────────────────────────

    fun addListener(listener: AdpEventListener) {
        listeners.add(listener)
    }

    fun removeListener(listener: AdpEventListener) {
        listeners.remove(listener)
    }

    // ── Internal HTTP Helpers ──────────────────────────────────────────

    private fun get(path: String, callback: (JsonElement) -> Unit) {
        val baseUrl = AdpSettingsState.getInstance().baseUrl.trimEnd('/')
        val request = Request.Builder().url("$baseUrl$path").build()

        httpClient.newCall(request).enqueue(object : Callback {
            override fun onFailure(call: Call, e: IOException) {
                log.warn("ADP GET $path failed: ${e.message}")
            }

            override fun onResponse(call: Call, response: Response) {
                response.use {
                    if (!it.isSuccessful) {
                        log.warn("ADP GET $path returned ${it.code}")
                        return
                    }
                    val body = it.body?.string() ?: return
                    val json = gson.fromJson(body, JsonElement::class.java)
                    val data = extractData(json)
                    if (data != null) callback(data)
                }
            }
        })
    }

    private fun post(path: String, jsonBody: String, callback: (JsonElement) -> Unit) {
        val baseUrl = AdpSettingsState.getInstance().baseUrl.trimEnd('/')
        val body = jsonBody.toRequestBody("application/json".toMediaType())
        val request = Request.Builder().url("$baseUrl$path").post(body).build()

        httpClient.newCall(request).enqueue(object : Callback {
            override fun onFailure(call: Call, e: IOException) {
                log.warn("ADP POST $path failed: ${e.message}")
            }

            override fun onResponse(call: Call, response: Response) {
                response.use {
                    if (!it.isSuccessful) return
                    val responseBody = it.body?.string() ?: return
                    val json = gson.fromJson(responseBody, JsonElement::class.java)
                    val data = extractData(json)
                    if (data != null) callback(data)
                }
            }
        })
    }

    private fun put(path: String, jsonBody: String, callback: (JsonElement) -> Unit) {
        val baseUrl = AdpSettingsState.getInstance().baseUrl.trimEnd('/')
        val body = jsonBody.toRequestBody("application/json".toMediaType())
        val request = Request.Builder().url("$baseUrl$path").put(body).build()

        httpClient.newCall(request).enqueue(object : Callback {
            override fun onFailure(call: Call, e: IOException) {
                log.warn("ADP PUT $path failed: ${e.message}")
            }

            override fun onResponse(call: Call, response: Response) {
                response.use {
                    if (!it.isSuccessful) return
                    val responseBody = it.body?.string() ?: return
                    val json = gson.fromJson(responseBody, JsonElement::class.java)
                    val data = extractData(json)
                    if (data != null) callback(data)
                }
            }
        })
    }

    /**
     * ADP API wraps responses in {id, data, error, success, status}.
     * Extract the `data` field, or return root if no wrapper.
     */
    private fun extractData(json: JsonElement): JsonElement? {
        if (json.isJsonObject) {
            val obj = json.asJsonObject
            if (obj.has("data")) return obj.get("data")
        }
        return json
    }

    // ── SSE Event Handling ─────────────────────────────────────────────

    /**
     * Handle incoming SSE events. ADP sends events when new debug entries
     * are flushed to storage. We re-fetch the entries list to get updated data.
     */
    private fun handleSseEvent(type: String?, data: String) {
        when (type) {
            "debug-update", null -> {
                // New debug entry available — refresh list
                fetchEntries()
            }
        }
    }

    private fun parseEntriesList(json: JsonElement): List<DebugEntrySummary> {
        return try {
            val listType = object : TypeToken<List<DebugEntrySummary>>() {}.type
            gson.fromJson(json, listType)
        } catch (e: Exception) {
            log.warn("Failed to parse entries: ${e.message}")
            emptyList()
        }
    }

    // ── Lifecycle ──────────────────────────────────────────────────────

    override fun dispose() {
        disconnect()
        httpClient.dispatcher.executorService.shutdown()
        httpClient.connectionPool.evictAll()
        sseClient.dispatcher.executorService.shutdown()
        sseClient.connectionPool.evictAll()
    }

    companion object {
        fun getInstance(): AdpApiClient =
            ApplicationManager.getApplication().getService(AdpApiClient::class.java)
    }
}

/**
 * Listener interface for SSE and data update events.
 * Implemented by ToolWindow, StatusBar, Notifications.
 */
interface AdpEventListener {
    fun onEntriesUpdated(entries: List<DebugEntrySummary>) {}
    fun onConnectionChanged(connected: Boolean) {}
    fun onNewEntry(entry: DebugEntrySummary) {}
}
