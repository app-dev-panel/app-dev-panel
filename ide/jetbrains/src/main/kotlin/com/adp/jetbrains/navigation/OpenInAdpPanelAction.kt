package com.adp.jetbrains.navigation

import com.adp.jetbrains.client.AdpApiClient
import com.adp.jetbrains.client.AnnotationType
import com.adp.jetbrains.settings.AdpSettingsState
import com.intellij.openapi.actionSystem.AnAction
import com.intellij.openapi.actionSystem.AnActionEvent
import com.intellij.openapi.actionSystem.CommonDataKeys
import com.intellij.openapi.fileEditor.FileEditorManager
import java.awt.Desktop
import java.net.URI

/**
 * Action: "Open in ADP Panel"
 *
 * Available in the editor context menu (right-click → ADP → Open in ADP Panel).
 * Opens the ADP web UI filtered to show debug data relevant to the current
 * cursor position.
 *
 * Behavior:
 * 1. Gets the current file path and line number from the editor
 * 2. Looks up annotations at that position in AdpDataCache
 * 3. Determines the best ADP panel to open:
 *    - If line has QUERY annotations → opens database panel
 *    - If line has EXCEPTION annotations → opens exception panel
 *    - If line has LOG annotations → opens log panel
 *    - If line has EVENT annotations → opens event panel
 *    - Otherwise → opens the debug entry overview
 * 4. Opens the URL in the default browser
 *
 * URL format:
 *   {baseUrl}/debug/{entryId}/{panel}?highlight={file}:{line}
 *
 * The ?highlight parameter is a proposed extension — the ADP frontend would
 * scroll to and highlight the relevant entry matching that source location.
 *
 * If no debug data exists for the current line, opens the entries list page.
 */
class OpenInAdpPanelAction : AnAction() {

    override fun actionPerformed(e: AnActionEvent) {
        val editor = e.getData(CommonDataKeys.EDITOR) ?: return
        val file = e.getData(CommonDataKeys.VIRTUAL_FILE) ?: return
        val project = e.project ?: return

        val baseUrl = AdpSettingsState.getInstance().baseUrl.trimEnd('/')
        val cache = AdpApiClient.getInstance().dataCache

        val filePath = file.path
        val caretLine = editor.caretModel.logicalPosition.line + 1 // 1-based

        // Check for annotations at current line
        val annotations = cache.getAnnotationsAt(filePath, caretLine)

        if (annotations.isEmpty()) {
            // No data at this line — open latest entry or entries list
            val latestId = cache.entries.firstOrNull()?.id
            val url = if (latestId != null) "$baseUrl/debug/$latestId" else baseUrl
            openUrl(url)
            return
        }

        // Determine best panel based on annotation types present
        val entryId = annotations.first().entryId
        val panel = when {
            annotations.any { it.type == AnnotationType.EXCEPTION } -> "exception"
            annotations.any { it.type == AnnotationType.QUERY } -> "database"
            annotations.any { it.type == AnnotationType.HTTP_REQUEST } -> "http-client"
            annotations.any { it.type == AnnotationType.EVENT } -> "event"
            annotations.any { it.type == AnnotationType.LOG } -> "log"
            annotations.any { it.type == AnnotationType.DEPRECATION } -> "deprecation"
            annotations.any { it.type == AnnotationType.DUMP } -> "var-dumper"
            else -> ""
        }

        // Build URL with highlight parameter for frontend auto-scroll
        val highlight = "$filePath:$caretLine"
        val url = "$baseUrl/debug/$entryId/$panel?highlight=${java.net.URLEncoder.encode(highlight, "UTF-8")}"
        openUrl(url)
    }

    override fun update(e: AnActionEvent) {
        // Only show when editor is active and ADP is connected
        val editor = e.getData(CommonDataKeys.EDITOR)
        e.presentation.isEnabledAndVisible = editor != null && AdpApiClient.getInstance().isConnected()
    }

    private fun openUrl(url: String) {
        try {
            Desktop.getDesktop().browse(URI(url))
        } catch (_: Exception) {}
    }
}

/**
 * Action: "Open Debug Entry"
 *
 * Opens the last debug entry that has data associated with the current line.
 * Unlike "Open in ADP Panel" which opens the browser, this action loads
 * the full entry detail in the ADP Tool Window within the IDE.
 */
class OpenDebugEntryAction : AnAction() {

    override fun actionPerformed(e: AnActionEvent) {
        val editor = e.getData(CommonDataKeys.EDITOR) ?: return
        val file = e.getData(CommonDataKeys.VIRTUAL_FILE) ?: return

        val cache = AdpApiClient.getInstance().dataCache
        val filePath = file.path
        val caretLine = editor.caretModel.logicalPosition.line + 1

        val annotations = cache.getAnnotationsAt(filePath, caretLine)
        if (annotations.isEmpty()) return

        val entryId = annotations.first().entryId

        // Fetch full detail and open in tool window
        AdpApiClient.getInstance().fetchEntryDetail(entryId) { detail ->
            // The tool window will update via the event listener
        }
    }

    override fun update(e: AnActionEvent) {
        val editor = e.getData(CommonDataKeys.EDITOR)
        val file = e.getData(CommonDataKeys.VIRTUAL_FILE)

        if (editor == null || file == null) {
            e.presentation.isEnabledAndVisible = false
            return
        }

        val cache = AdpApiClient.getInstance().dataCache
        val caretLine = editor.caretModel.logicalPosition.line + 1
        val hasAnnotations = cache.getAnnotationsAt(file.path, caretLine).isNotEmpty()

        e.presentation.isEnabledAndVisible = hasAnnotations
    }
}
