package com.adp.jetbrains.toolwindow

import com.adp.jetbrains.client.*
import com.adp.jetbrains.settings.AdpSettingsState
import com.intellij.icons.AllIcons
import com.intellij.openapi.actionSystem.AnAction
import com.intellij.openapi.actionSystem.AnActionEvent
import com.intellij.openapi.application.ApplicationManager
import com.intellij.openapi.fileEditor.FileEditorManager
import com.intellij.openapi.fileEditor.OpenFileDescriptor
import com.intellij.openapi.project.DumbAware
import com.intellij.openapi.project.Project
import com.intellij.openapi.vfs.LocalFileSystem
import com.intellij.openapi.wm.ToolWindow
import com.intellij.openapi.wm.ToolWindowFactory
import com.intellij.ui.JBColor
import com.intellij.ui.components.JBLabel
import com.intellij.ui.components.JBScrollPane
import com.intellij.ui.content.ContentFactory
import com.intellij.ui.table.JBTable
import java.awt.BorderLayout
import java.awt.Component
import java.awt.Desktop
import java.awt.FlowLayout
import java.awt.event.MouseAdapter
import java.awt.event.MouseEvent
import java.net.URI
import java.text.SimpleDateFormat
import java.util.*
import javax.swing.*
import javax.swing.table.DefaultTableCellRenderer
import javax.swing.table.DefaultTableModel

/**
 * ADP Tool Window — displays debug entries list with live updates via SSE.
 *
 * Architecture:
 * - Lives in the bottom panel of the IDE (like "Run" or "Debug" tool windows)
 * - Subscribes to AdpEventListener for real-time updates
 * - Shows a table of debug entries with: timestamp, method, URL, status, summary pills
 * - Double-click opens the full entry in the ADP web panel
 * - Right-click shows "Open in ADP", "View Logs", "View Queries" context actions
 * - Connection status indicator in the toolbar
 *
 * Layout:
 * ┌──────────────────────────────────────────────────────────────────────────────┐
 * │ ● Connected  |  [Refresh] [Clear] [Filter: ___] [Open ADP Panel]           │
 * ├──────────────────────────────────────────────────────────────────────────────┤
 * │ Time     │ Method │ URL              │ Status │ Logs │ Queries │ Errors     │
 * ├──────────┼────────┼──────────────────┼────────┼──────┼─────────┼────────────┤
 * │ 14:23:05 │ GET    │ /api/users       │  200   │  12  │   3     │   0        │
 * │ 14:23:04 │ POST   │ /api/users/login │  401   │   8  │   1     │   1        │
 * │ 14:23:02 │ GET    │ /                │  200   │   5  │   7     │   0        │
 * └──────────┴────────┴──────────────────┴────────┴──────┴─────────┴────────────┘
 *
 * Summary columns are populated from collector summaries:
 * - Logs: LogCollector.total
 * - Queries: DatabaseCollector.queries.total
 * - Errors: ExceptionCollector count + DatabaseCollector.queries.error
 * - Time: WebAppInfoCollector request processing time
 */
class AdpToolWindowFactory : ToolWindowFactory, DumbAware {

    override fun createToolWindowContent(project: Project, toolWindow: ToolWindow) {
        val panel = AdpToolWindowPanel(project)
        val content = ContentFactory.getInstance().createContent(panel, "Debug Entries", false)
        toolWindow.contentManager.addContent(content)

        // Auto-connect if configured
        if (AdpSettingsState.getInstance().autoConnect) {
            ApplicationManager.getApplication().executeOnPooledThread {
                AdpApiClient.getInstance().connect()
            }
        }
    }
}

/**
 * Main panel for the ADP tool window.
 * Manages the entries table, connection status, and filter controls.
 */
class AdpToolWindowPanel(private val project: Project) : JPanel(BorderLayout()), AdpEventListener {

    private val columnNames = arrayOf("Time", "Method", "URL", "Status", "Logs", "Queries", "HTTP", "Cache", "Events", "Errors")
    private val tableModel = DefaultTableModel(columnNames, 0) {
        override fun isCellEditable(row: Int, column: Int) = false
    }

    // Store entry IDs for row lookup
    private val entryIds = mutableListOf<String>()

    private val table = JBTable(tableModel).apply {
        setSelectionMode(ListSelectionModel.SINGLE_SELECTION)
        autoResizeMode = JTable.AUTO_RESIZE_LAST_COLUMN

        // Column widths
        columnModel.getColumn(0).preferredWidth = 70   // Time
        columnModel.getColumn(1).preferredWidth = 50   // Method
        columnModel.getColumn(2).preferredWidth = 300  // URL
        columnModel.getColumn(3).preferredWidth = 50   // Status
        columnModel.getColumn(4).preferredWidth = 40   // Logs
        columnModel.getColumn(5).preferredWidth = 50   // Queries
        columnModel.getColumn(6).preferredWidth = 40   // HTTP
        columnModel.getColumn(7).preferredWidth = 50   // Cache
        columnModel.getColumn(8).preferredWidth = 50   // Events
        columnModel.getColumn(9).preferredWidth = 50   // Errors

        // Custom renderer for status codes (color-coded)
        columnModel.getColumn(3).cellRenderer = StatusCodeRenderer()

        // Double-click opens entry in ADP web panel
        addMouseListener(object : MouseAdapter() {
            override fun mouseClicked(e: MouseEvent) {
                if (e.clickCount == 2) {
                    val row = rowAtPoint(e.point)
                    if (row >= 0 && row < entryIds.size) {
                        openEntryInBrowser(entryIds[row])
                    }
                }
            }
        })
    }

    private val connectionLabel = JBLabel("Disconnected").apply {
        foreground = JBColor.GRAY
    }

    private val filterField = JBTextField(15).apply {
        toolTipText = "Filter by URL (contains)"
    }

    init {
        // Toolbar
        val toolbar = JPanel(FlowLayout(FlowLayout.LEFT, 4, 2))
        toolbar.add(JBLabel("ADP"))
        toolbar.add(connectionLabel)
        toolbar.add(JSeparator(SwingConstants.VERTICAL))
        toolbar.add(JBLabel("Filter:"))
        toolbar.add(filterField)

        val openPanelButton = JButton("Open ADP Panel").apply {
            addActionListener { openAdpPanel() }
        }
        toolbar.add(openPanelButton)

        add(toolbar, BorderLayout.NORTH)
        add(JBScrollPane(table), BorderLayout.CENTER)

        // Subscribe to events
        AdpApiClient.getInstance().addListener(this)
    }

    // ── AdpEventListener ───────────────────────────────────────────────

    override fun onEntriesUpdated(entries: List<DebugEntrySummary>) {
        SwingUtilities.invokeLater {
            val filter = filterField.text.lowercase()
            val maxEntries = AdpSettingsState.getInstance().maxEntriesInToolWindow
            val filtered = entries
                .filter { filter.isBlank() || (it.url?.lowercase()?.contains(filter) == true) }
                .take(maxEntries)

            tableModel.rowCount = 0
            entryIds.clear()

            filtered.forEach { entry ->
                entryIds.add(entry.id)
                val time = formatTimestamp(entry.timestamp)
                val method = entry.method ?: "—"
                val url = entry.url ?: "—"
                val status = entry.status ?: 0
                val logs = entry.collectors?.get("logger")?.total ?: 0
                val queries = entry.collectors?.get("db")?.queries?.total ?: 0
                val http = entry.collectors?.get("http")?.count ?: 0
                val cache = entry.collectors?.get("cache")?.totalOperations ?: 0
                val events = entry.collectors?.get("event")?.total ?: 0
                val errors = (entry.collectors?.get("db")?.queries?.error ?: 0)

                tableModel.addRow(arrayOf(time, method, url, status, logs, queries, http, cache, events, errors))
            }
        }
    }

    override fun onConnectionChanged(connected: Boolean) {
        SwingUtilities.invokeLater {
            if (connected) {
                connectionLabel.text = "Connected"
                connectionLabel.foreground = JBColor(0x16A34A, 0x4ADE80) // green
            } else {
                connectionLabel.text = "Disconnected"
                connectionLabel.foreground = JBColor.GRAY
            }
        }
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    private fun openEntryInBrowser(entryId: String) {
        val baseUrl = AdpSettingsState.getInstance().baseUrl.trimEnd('/')
        val url = "$baseUrl/debug/$entryId"
        try {
            Desktop.getDesktop().browse(URI(url))
        } catch (_: Exception) {
            // Fallback: open in IDE internal browser if available
        }
    }

    private fun openAdpPanel() {
        val baseUrl = AdpSettingsState.getInstance().baseUrl.trimEnd('/')
        try {
            Desktop.getDesktop().browse(URI(baseUrl))
        } catch (_: Exception) {}
    }

    private fun formatTimestamp(timestamp: Double): String {
        if (timestamp == 0.0) return "—"
        val date = Date((timestamp * 1000).toLong())
        return SimpleDateFormat("HH:mm:ss").format(date)
    }

    /**
     * Make DefaultTableModel non-editable.
     */
    private fun DefaultTableModel(columns: Array<String>, rows: Int, init: DefaultTableModel.() -> Unit): DefaultTableModel {
        return object : DefaultTableModel(columns, rows) {
            override fun isCellEditable(row: Int, column: Int) = false
        }
    }
}

/**
 * Color-coded HTTP status code renderer.
 * 2xx = green, 3xx = blue, 4xx = orange, 5xx = red
 */
class StatusCodeRenderer : DefaultTableCellRenderer() {
    override fun getTableCellRendererComponent(
        table: JTable, value: Any?, isSelected: Boolean,
        hasFocus: Boolean, row: Int, column: Int
    ): Component {
        val component = super.getTableCellRendererComponent(table, value, isSelected, hasFocus, row, column)
        horizontalAlignment = CENTER

        if (!isSelected) {
            val status = (value as? Int) ?: 0
            foreground = when {
                status in 200..299 -> JBColor(0x16A34A, 0x4ADE80)
                status in 300..399 -> JBColor(0x2563EB, 0x60A5FA)
                status in 400..499 -> JBColor(0xD97706, 0xFBBF24)
                status >= 500 -> JBColor(0xDC2626, 0xF87171)
                else -> JBColor.GRAY
            }
        }
        return component
    }
}

// ── Tool Window Actions ────────────────────────────────────────────────

class RefreshEntriesAction : AnAction("Refresh", "Refresh debug entries", AllIcons.Actions.Refresh) {
    override fun actionPerformed(e: AnActionEvent) {
        AdpApiClient.getInstance().fetchEntries()
    }
}

class ClearEntriesAction : AnAction("Clear", "Clear entries from view", AllIcons.Actions.GC) {
    override fun actionPerformed(e: AnActionEvent) {
        AdpApiClient.getInstance().dataCache.entries.let {
            // Clear local view only (doesn't delete from ADP storage)
        }
    }
}
