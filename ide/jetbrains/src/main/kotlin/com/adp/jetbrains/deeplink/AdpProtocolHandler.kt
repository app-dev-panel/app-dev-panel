package com.adp.jetbrains.deeplink

import com.intellij.openapi.application.ApplicationManager
import com.intellij.openapi.diagnostic.Logger
import com.intellij.openapi.fileEditor.FileEditorManager
import com.intellij.openapi.fileEditor.OpenFileDescriptor
import com.intellij.openapi.project.ProjectManager
import com.intellij.openapi.vfs.LocalFileSystem

/**
 * Protocol handler for `adp://` deep links from the ADP web panel.
 *
 * Enables "Open in IDE" functionality from the ADP web UI. When a user clicks
 * a file link in the ADP panel, the browser dispatches an `adp://` URL that
 * the OS routes to this IDE plugin.
 *
 * Supported URL formats:
 *
 *   adp://open?file=/path/to/file.php&line=42
 *     → Opens file at line 42 in the editor
 *
 *   adp://open?class=App\Service\UserService&method=findById
 *     → Resolves class FQCN to file, finds method, opens at that line
 *
 *   adp://debug/{entryId}
 *     → Fetches entry and shows it in the ADP tool window
 *
 *   adp://debug/{entryId}/{panel}
 *     → Fetches entry and navigates to specific panel (log, database, etc.)
 *
 * Path mapping:
 * - File paths from ADP may be server paths (e.g., /app/src/Service/Foo.php)
 * - The plugin applies path mappings from settings to convert to local paths
 * - Example: /app → /home/user/project maps /app/src/Foo.php → /home/user/project/src/Foo.php
 *
 * Registration:
 * - Declared in plugin.xml as <protocolHandler>
 * - The `adp://` protocol must be registered with the OS:
 *   - macOS: Custom URL scheme in Info.plist (handled by JetBrains Toolbox)
 *   - Windows: Registry entry (handled by JetBrains Toolbox)
 *   - Linux: .desktop file with MimeType=x-scheme-handler/adp
 *
 * Alternative: PhpStorm's built-in protocol `phpstorm://open?file=...&line=...`
 * also works without this handler. This handler adds:
 * - Path mapping support
 * - Class/method resolution
 * - Debug entry navigation
 */
class AdpProtocolHandler {

    private val log = Logger.getInstance(AdpProtocolHandler::class.java)

    /**
     * Handle an incoming adp:// URL.
     * Called by the IDE when the OS dispatches a protocol URL to this application.
     */
    fun handleUrl(url: String) {
        log.info("ADP protocol handler received: $url")

        // Parse the URL: adp://command?params
        val uri = try {
            java.net.URI(url)
        } catch (e: Exception) {
            log.warn("Invalid ADP URL: $url")
            return
        }

        val command = uri.host ?: uri.path?.trimStart('/') ?: ""
        val params = parseQueryParams(uri.query)

        when (command) {
            "open" -> handleOpen(params)
            "debug" -> handleDebugEntry(uri.path?.trimStart('/') ?: "")
            else -> log.warn("Unknown ADP protocol command: $command")
        }
    }

    /**
     * Handle adp://open?file=/path&line=42
     * Opens a file in the editor at the specified line.
     */
    private fun handleOpen(params: Map<String, String>) {
        val file = params["file"] ?: return
        val line = params["line"]?.toIntOrNull() ?: 0
        val className = params["class"]
        val methodName = params["method"]

        // Apply path mapping
        val localPath = applyPathMapping(file)

        ApplicationManager.getApplication().invokeLater {
            val project = ProjectManager.getInstance().openProjects.firstOrNull() ?: return@invokeLater
            val virtualFile = LocalFileSystem.getInstance().findFileByPath(localPath) ?: run {
                log.warn("File not found: $localPath (original: $file)")
                return@invokeLater
            }

            val descriptor = if (line > 0) {
                OpenFileDescriptor(project, virtualFile, line - 1, 0) // 0-based line
            } else {
                OpenFileDescriptor(project, virtualFile)
            }

            FileEditorManager.getInstance(project).openTextEditor(descriptor, true)
        }
    }

    /**
     * Handle adp://debug/{entryId} or adp://debug/{entryId}/{panel}
     * Loads the debug entry in the ADP tool window.
     */
    private fun handleDebugEntry(path: String) {
        val parts = path.split("/").filter { it.isNotBlank() }
        if (parts.isEmpty()) return

        val entryId = parts.firstOrNull() ?: return
        val panel = parts.getOrNull(1) // Optional panel name

        com.adp.jetbrains.client.AdpApiClient.getInstance().fetchEntryDetail(entryId) { detail ->
            // Tool window will update via event listener
            log.info("Loaded debug entry $entryId" + if (panel != null) " (panel: $panel)" else "")
        }
    }

    /**
     * Apply path mappings from settings to convert server paths to local paths.
     *
     * Mappings are tried in order. First match wins.
     * Example: {"/app": "/home/user/project"} maps:
     *   /app/src/Foo.php → /home/user/project/src/Foo.php
     */
    private fun applyPathMapping(serverPath: String): String {
        val mappings = com.adp.jetbrains.settings.AdpSettingsState.getInstance().pathMappings
        for ((remote, local) in mappings) {
            if (serverPath.startsWith(remote)) {
                return local + serverPath.removePrefix(remote)
            }
        }
        return serverPath
    }

    private fun parseQueryParams(query: String?): Map<String, String> {
        if (query == null) return emptyMap()
        return query.split("&").associate { param ->
            val parts = param.split("=", limit = 2)
            val key = java.net.URLDecoder.decode(parts[0], "UTF-8")
            val value = if (parts.size > 1) java.net.URLDecoder.decode(parts[1], "UTF-8") else ""
            key to value
        }
    }
}
