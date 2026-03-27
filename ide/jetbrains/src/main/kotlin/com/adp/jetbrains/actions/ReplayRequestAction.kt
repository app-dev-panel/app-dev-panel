package com.adp.jetbrains.actions

import com.adp.jetbrains.client.AdpApiClient
import com.adp.jetbrains.settings.AdpSettingsState
import com.google.gson.Gson
import com.google.gson.JsonObject
import com.intellij.notification.NotificationGroupManager
import com.intellij.notification.NotificationType
import com.intellij.openapi.actionSystem.AnAction
import com.intellij.openapi.actionSystem.AnActionEvent
import com.intellij.openapi.project.ProjectManager
import java.awt.Toolkit
import java.awt.datatransfer.StringSelection

/**
 * Action: "Replay Last Request"
 *
 * Re-executes the last HTTP request captured by ADP.
 *
 * How it works:
 * 1. Gets the latest debug entry from AdpDataCache
 * 2. Extracts request data (method, URL, headers, body) from RequestCollector
 * 3. Sends PUT /inspect/api/request to ADP with the original request data
 * 4. ADP re-executes the request against the application
 * 5. Shows a notification with the new response status
 *
 * Use cases:
 * - Quickly re-test after a code change without switching to browser/Postman
 * - Reproduce an error from the debug log
 * - Compare responses before/after a fix
 *
 * The action is available:
 * - In the ADP menu (Tools → ADP → Replay Last Request)
 * - In the ADP Tool Window toolbar
 * - Via keyboard shortcut (configurable)
 *
 * Requirements:
 * - ADP must be connected
 * - At least one debug entry must exist
 * - The entry must have RequestCollector data
 */
class ReplayRequestAction : AnAction() {

    private val gson = Gson()

    override fun actionPerformed(e: AnActionEvent) {
        val cache = AdpApiClient.getInstance().dataCache
        val latestEntry = cache.entries.firstOrNull()
        if (latestEntry == null) {
            showNotification("No debug entries available", NotificationType.WARNING)
            return
        }

        // Fetch detail to get request data
        AdpApiClient.getInstance().fetchEntryDetail(latestEntry.id) { detail ->
            val requestData = detail.data?.entries?.find {
                it.key.contains("RequestCollector")
            }?.value

            if (requestData == null || !requestData.isJsonObject) {
                showNotification("No request data in latest entry", NotificationType.WARNING)
                return@fetchEntryDetail
            }

            // Replay the request
            AdpApiClient.getInstance().replayRequest(requestData.asJsonObject) { response ->
                val status = response.asJsonObject?.get("responseStatusCode")?.asInt ?: 0
                val method = latestEntry.method ?: "?"
                val url = latestEntry.url ?: "?"
                showNotification(
                    "Replayed $method $url → $status",
                    if (status in 200..299) NotificationType.INFORMATION else NotificationType.WARNING
                )

                // Refresh entries to show the new debug entry created by replay
                AdpApiClient.getInstance().fetchEntries()
            }
        }
    }

    override fun update(e: AnActionEvent) {
        e.presentation.isEnabled =
            AdpApiClient.getInstance().isConnected() &&
            AdpApiClient.getInstance().dataCache.entries.isNotEmpty()
    }

    private fun showNotification(message: String, type: NotificationType) {
        val project = ProjectManager.getInstance().openProjects.firstOrNull() ?: return
        NotificationGroupManager.getInstance()
            .getNotificationGroup("ADP Notifications")
            .createNotification("ADP", message, type)
            .notify(project)
    }
}

/**
 * Action: "Copy as cURL"
 *
 * Copies the last HTTP request as a cURL command to the clipboard.
 *
 * How it works:
 * 1. Gets the latest debug entry's request data
 * 2. Sends POST /inspect/api/curl/build to ADP
 * 3. ADP generates a cURL command with method, headers, body, cookies
 * 4. Copies the result to the system clipboard
 * 5. Shows a notification confirming the copy
 *
 * The generated cURL includes:
 * - HTTP method (-X POST)
 * - All request headers (-H "Content-Type: application/json")
 * - Request body (-d '{"key":"value"}')
 * - Cookies (-b "session=abc123")
 * - URL
 *
 * Use cases:
 * - Share a request with a colleague
 * - Test in terminal
 * - Import into Postman/Insomnia
 * - Debug from command line
 *
 * Integration with JetBrains HTTP Client:
 * The cURL can be pasted into a .http file and converted to HTTP Client format
 * using JetBrains' built-in "Convert cURL to HTTP Request" action.
 */
class CopyCurlAction : AnAction() {

    private val gson = Gson()

    override fun actionPerformed(e: AnActionEvent) {
        val cache = AdpApiClient.getInstance().dataCache
        val latestEntry = cache.entries.firstOrNull()
        if (latestEntry == null) {
            showNotification("No debug entries available", NotificationType.WARNING)
            return
        }

        AdpApiClient.getInstance().fetchEntryDetail(latestEntry.id) { detail ->
            val requestData = detail.data?.entries?.find {
                it.key.contains("RequestCollector")
            }?.value

            if (requestData == null || !requestData.isJsonObject) {
                showNotification("No request data in latest entry", NotificationType.WARNING)
                return@fetchEntryDetail
            }

            AdpApiClient.getInstance().buildCurl(requestData.asJsonObject) { curl ->
                // Copy to clipboard
                val clipboard = Toolkit.getDefaultToolkit().systemClipboard
                clipboard.setContents(StringSelection(curl), null)
                showNotification("cURL command copied to clipboard", NotificationType.INFORMATION)
            }
        }
    }

    override fun update(e: AnActionEvent) {
        e.presentation.isEnabled =
            AdpApiClient.getInstance().isConnected() &&
            AdpApiClient.getInstance().dataCache.entries.isNotEmpty()
    }

    private fun showNotification(message: String, type: NotificationType) {
        val project = ProjectManager.getInstance().openProjects.firstOrNull() ?: return
        NotificationGroupManager.getInstance()
            .getNotificationGroup("ADP Notifications")
            .createNotification("ADP", message, type)
            .notify(project)
    }
}
