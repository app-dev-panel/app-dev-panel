package com.adp.jetbrains.statusbar

import com.adp.jetbrains.client.AdpApiClient
import com.adp.jetbrains.client.AdpEventListener
import com.adp.jetbrains.client.DebugEntrySummary
import com.adp.jetbrains.settings.AdpSettingsState
import com.intellij.openapi.project.Project
import com.intellij.openapi.wm.StatusBar
import com.intellij.openapi.wm.StatusBarWidget
import com.intellij.openapi.wm.StatusBarWidgetFactory
import com.intellij.util.Consumer
import java.awt.Component
import java.awt.Desktop
import java.awt.event.MouseEvent
import java.net.URI
import javax.swing.SwingUtilities

/**
 * Status bar widget showing the last ADP debug entry summary.
 *
 * Appears in the IDE status bar (bottom-right area) as a compact text:
 *
 *   Normal:     ADP: GET /api/users → 200 | 3 queries, 12 logs
 *   Error:      ADP: POST /api/auth → 500 | 1 exception
 *   Slow:       ADP: GET /dashboard → 200 | 15 queries (342ms)
 *   Disconnected: ADP: ●
 *
 * Color coding:
 * - Green dot: connected, last request OK (2xx)
 * - Yellow dot: connected, last request warning (4xx) or slow queries
 * - Red dot: connected, last request error (5xx) or exception
 * - Gray dot: disconnected
 *
 * Click action: Opens the last debug entry in the ADP web panel.
 *
 * Updates:
 * - Receives real-time updates via AdpEventListener
 * - Shows data from the most recent debug entry
 * - Auto-refreshes when new entries arrive via SSE
 */
class AdpStatusBarWidgetFactory : StatusBarWidgetFactory {
    override fun getId(): String = "AdpStatusBar"
    override fun getDisplayName(): String = "ADP Status"
    override fun isAvailable(project: Project): Boolean = true
    override fun createWidget(project: Project): StatusBarWidget = AdpStatusBarWidget(project)
}

class AdpStatusBarWidget(private val project: Project) :
    StatusBarWidget,
    StatusBarWidget.TextPresentation,
    AdpEventListener {

    private var statusBar: StatusBar? = null
    private var lastEntry: DebugEntrySummary? = null
    private var connected = false

    // ── StatusBarWidget ────────────────────────────────────────────────

    override fun ID(): String = "AdpStatusBar"

    override fun install(statusBar: StatusBar) {
        this.statusBar = statusBar
        AdpApiClient.getInstance().addListener(this)
        connected = AdpApiClient.getInstance().isConnected()
    }

    override fun dispose() {
        AdpApiClient.getInstance().removeListener(this)
    }

    override fun getPresentation(): StatusBarWidget.WidgetPresentation = this

    // ── TextPresentation ───────────────────────────────────────────────

    override fun getText(): String {
        if (!connected) return "ADP: ●"

        val entry = lastEntry ?: return "ADP: waiting..."

        val method = entry.method ?: ""
        val url = truncateUrl(entry.url ?: "", 30)
        val status = entry.status ?: 0

        // Summary pills
        val pills = mutableListOf<String>()

        val queryCount = entry.collectors?.get("db")?.queries?.total ?: 0
        if (queryCount > 0) pills.add("${queryCount}q")

        val logCount = entry.collectors?.get("logger")?.total ?: 0
        if (logCount > 0) pills.add("${logCount}L")

        val errorCount = entry.collectors?.get("db")?.queries?.error ?: 0
        if (errorCount > 0) pills.add("${errorCount}err")

        val httpCount = entry.collectors?.get("http")?.count ?: 0
        if (httpCount > 0) pills.add("${httpCount}http")

        val summary = if (pills.isNotEmpty()) " | ${pills.joinToString(", ")}" else ""

        return "ADP: $method $url → $status$summary"
    }

    override fun getTooltipText(): String {
        if (!connected) return "ADP: Not connected. Configure in Settings → Tools → ADP."

        val entry = lastEntry ?: return "ADP: Connected, waiting for debug entries..."

        val sb = StringBuilder()
        sb.appendLine("Last request: ${entry.method} ${entry.url}")
        sb.appendLine("Status: ${entry.status}")
        sb.appendLine("Entry ID: ${entry.id}")

        entry.collectors?.forEach { (name, summary) ->
            val total = summary.total ?: summary.count ?: summary.queries?.total ?: summary.totalOperations
            if (total != null && total > 0) {
                sb.appendLine("  $name: $total")
            }
        }

        sb.appendLine("\nClick to open in ADP Panel")
        return sb.toString()
    }

    override fun getAlignment(): Float = Component.RIGHT_ALIGNMENT

    override fun getClickConsumer(): Consumer<MouseEvent> = Consumer {
        val entry = lastEntry ?: return@Consumer
        val baseUrl = AdpSettingsState.getInstance().baseUrl.trimEnd('/')
        val url = "$baseUrl/debug/${entry.id}"
        try {
            Desktop.getDesktop().browse(URI(url))
        } catch (_: Exception) {}
    }

    // ── AdpEventListener ───────────────────────────────────────────────

    override fun onEntriesUpdated(entries: List<DebugEntrySummary>) {
        lastEntry = entries.firstOrNull()
        SwingUtilities.invokeLater {
            statusBar?.updateWidget(ID())
        }
    }

    override fun onConnectionChanged(connected: Boolean) {
        this.connected = connected
        SwingUtilities.invokeLater {
            statusBar?.updateWidget(ID())
        }
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    private fun truncateUrl(url: String, maxLen: Int): String {
        if (url.length <= maxLen) return url
        // Show last maxLen chars with leading ...
        return "..." + url.takeLast(maxLen - 3)
    }
}
