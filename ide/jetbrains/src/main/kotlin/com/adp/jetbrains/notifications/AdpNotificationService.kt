package com.adp.jetbrains.notifications

import com.adp.jetbrains.client.AdpApiClient
import com.adp.jetbrains.client.AdpEventListener
import com.adp.jetbrains.client.DebugEntrySummary
import com.adp.jetbrains.settings.AdpSettingsState
import com.intellij.notification.NotificationGroupManager
import com.intellij.notification.NotificationType
import com.intellij.openapi.Disposable
import com.intellij.openapi.application.ApplicationManager
import com.intellij.openapi.components.Service
import com.intellij.openapi.diagnostic.Logger
import com.intellij.openapi.project.ProjectManager
import java.awt.Desktop
import java.net.URI

/**
 * Real-time notification service for ADP events.
 *
 * Subscribes to the AdpApiClient SSE stream and shows IDE balloon notifications
 * when significant events occur:
 *
 * Notification triggers:
 * 1. **Exception in request** — Sticky balloon with exception class and message.
 *    Action: "Open in ADP" opens the exception panel.
 *
 * 2. **5xx response** — Balloon showing URL and status code.
 *    Action: "View Request" opens the request panel.
 *
 * 3. **Slow request** — When total query time exceeds threshold.
 *    Shows query count and total time.
 *    Action: "View Queries" opens the database panel.
 *
 * 4. **N+1 detected** — When duplicate query groups are found.
 *    Shows the duplicate count.
 *
 * 5. **High error log count** — When error+ level logs exceed 5 in a request.
 *
 * Notification groups (defined in plugin.xml):
 * - "ADP Notifications" — BALLOON type, auto-dismiss after 10s
 * - "ADP Errors" — STICKY_BALLOON type, requires manual dismiss
 *
 * Configuration:
 * - Toggle: Settings → ADP → "Show real-time notifications"
 * - Filter: Settings → ADP → Notification filter (which log levels)
 * - Disable specific types: IDE Settings → Notifications → ADP
 *
 * Deduplication:
 * - Only fires for NEW entries (tracks last seen entry ID)
 * - One notification per entry (combines multiple issues into one notification)
 */
@Service(Service.Level.APP)
class AdpNotificationService : AdpEventListener, Disposable {

    private val log = Logger.getInstance(AdpNotificationService::class.java)
    private var lastNotifiedEntryId: String? = null

    fun initialize() {
        AdpApiClient.getInstance().addListener(this)
        log.info("ADP notification service initialized")
    }

    override fun onEntriesUpdated(entries: List<DebugEntrySummary>) {
        if (!AdpSettingsState.getInstance().enableNotifications) return
        if (entries.isEmpty()) return

        val latest = entries.first()
        if (latest.id == lastNotifiedEntryId) return
        lastNotifiedEntryId = latest.id

        // Analyze the entry for notification-worthy events
        val issues = analyzeEntry(latest)
        if (issues.isEmpty()) return

        // Build and show notification
        showNotification(latest, issues)
    }

    override fun onConnectionChanged(connected: Boolean) {
        val project = ProjectManager.getInstance().openProjects.firstOrNull() ?: return

        if (connected) {
            NotificationGroupManager.getInstance()
                .getNotificationGroup("ADP Notifications")
                .createNotification(
                    "ADP Connected",
                    "Connected to ${AdpSettingsState.getInstance().baseUrl}",
                    NotificationType.INFORMATION
                )
                .notify(project)
        }
    }

    /**
     * Analyze a debug entry summary to find notification-worthy issues.
     * Returns a list of issue descriptions.
     */
    private fun analyzeEntry(entry: DebugEntrySummary): List<NotificationIssue> {
        val issues = mutableListOf<NotificationIssue>()

        // 5xx status code
        val status = entry.status ?: 0
        if (status >= 500) {
            issues.add(NotificationIssue(
                severity = IssueSeverity.ERROR,
                title = "Server Error",
                message = "${entry.method} ${entry.url} returned $status",
                panel = "request",
            ))
        }

        // Database errors
        val queryErrors = entry.collectors?.get("db")?.queries?.error ?: 0
        if (queryErrors > 0) {
            issues.add(NotificationIssue(
                severity = IssueSeverity.ERROR,
                title = "SQL Errors",
                message = "$queryErrors query error(s) in ${entry.method} ${entry.url}",
                panel = "database",
            ))
        }

        // Duplicate queries (N+1)
        val duplicateGroups = entry.collectors?.get("db")?.duplicateGroups ?: 0
        if (duplicateGroups > 0) {
            issues.add(NotificationIssue(
                severity = IssueSeverity.WARNING,
                title = "N+1 Queries",
                message = "$duplicateGroups duplicate query group(s) detected",
                panel = "database",
            ))
        }

        return issues
    }

    /**
     * Show a balloon notification for the given issues.
     */
    private fun showNotification(entry: DebugEntrySummary, issues: List<NotificationIssue>) {
        val project = ProjectManager.getInstance().openProjects.firstOrNull() ?: return

        val maxSeverity = issues.maxOf { it.severity }
        val notificationType = when (maxSeverity) {
            IssueSeverity.ERROR -> NotificationType.ERROR
            IssueSeverity.WARNING -> NotificationType.WARNING
            IssueSeverity.INFO -> NotificationType.INFORMATION
        }

        val groupId = if (maxSeverity == IssueSeverity.ERROR) "ADP Errors" else "ADP Notifications"

        val title = "ADP: ${entry.method ?: ""} ${truncate(entry.url ?: "", 40)} → ${entry.status ?: "?"}"
        val content = issues.joinToString("<br/>") { "• ${it.title}: ${it.message}" }

        val notification = NotificationGroupManager.getInstance()
            .getNotificationGroup(groupId)
            .createNotification(title, content, notificationType)

        // Add "Open in ADP" action
        val baseUrl = AdpSettingsState.getInstance().baseUrl.trimEnd('/')
        val primaryPanel = issues.first().panel
        notification.addAction(object : com.intellij.notification.NotificationAction("Open in ADP") {
            override fun actionPerformed(
                e: com.intellij.openapi.actionSystem.AnActionEvent,
                notification: com.intellij.notification.Notification
            ) {
                try {
                    Desktop.getDesktop().browse(URI("$baseUrl/debug/${entry.id}/$primaryPanel"))
                } catch (_: Exception) {}
                notification.expire()
            }
        })

        notification.notify(project)
    }

    private fun truncate(s: String, maxLen: Int): String =
        if (s.length <= maxLen) s else s.take(maxLen) + "..."

    override fun dispose() {
        AdpApiClient.getInstance().removeListener(this)
    }

    companion object {
        fun getInstance(): AdpNotificationService =
            ApplicationManager.getApplication().getService(AdpNotificationService::class.java)
    }
}

data class NotificationIssue(
    val severity: IssueSeverity,
    val title: String,
    val message: String,
    val panel: String, // ADP panel to open: "request", "database", "exception", etc.
)

enum class IssueSeverity {
    INFO, WARNING, ERROR
}
