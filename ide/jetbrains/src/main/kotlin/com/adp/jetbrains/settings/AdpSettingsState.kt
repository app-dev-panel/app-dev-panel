package com.adp.jetbrains.settings

import com.intellij.openapi.application.ApplicationManager
import com.intellij.openapi.components.PersistentStateComponent
import com.intellij.openapi.components.Service
import com.intellij.openapi.components.State
import com.intellij.openapi.components.Storage

/**
 * Persistent settings for the ADP JetBrains plugin.
 *
 * Stored in the IDE's global config directory (not per-project) because ADP
 * connection settings are typically shared across projects on the same machine.
 *
 * Settings:
 * - baseUrl: ADP API base URL (e.g., "http://localhost:8080")
 * - autoConnect: Whether to connect to SSE on IDE startup
 * - pathMappings: Remote-to-local path mappings for Docker/Vagrant/WSL
 * - slowQueryThresholdMs: Threshold for "slow query" inspection highlighting
 * - enableGutterIcons: Toggle gutter icon annotations
 * - enableInlayHints: Toggle inlay hint annotations
 * - enableNotifications: Toggle real-time error notifications
 * - maxEntriesInToolWindow: How many entries to show in the tool window list
 * - notificationFilter: Which severity levels trigger notifications
 */
@Service(Service.Level.APP)
@State(
    name = "com.adp.jetbrains.settings.AdpSettingsState",
    storages = [Storage("adp-plugin.xml")]
)
class AdpSettingsState : PersistentStateComponent<AdpSettingsState.State> {

    data class State(
        var baseUrl: String = "http://localhost:8080",
        var autoConnect: Boolean = true,
        var pathMappings: MutableMap<String, String> = mutableMapOf(),
        var slowQueryThresholdMs: Int = 100,
        var duplicateQueryThreshold: Int = 2,
        var enableGutterIcons: Boolean = true,
        var enableInlayHints: Boolean = true,
        var enableNotifications: Boolean = true,
        var enableLiveTail: Boolean = true,
        var maxEntriesInToolWindow: Int = 50,
        var notificationFilter: MutableSet<String> = mutableSetOf("error", "critical", "emergency", "alert"),
        var showQueryDuration: Boolean = true,
        var showLogLevel: Boolean = true,
        var showHttpStatus: Boolean = true,
    )

    private var state = State()

    override fun getState(): State = state

    override fun loadState(state: State) {
        this.state = state
    }

    // Convenience accessors
    var baseUrl: String
        get() = state.baseUrl
        set(value) { state.baseUrl = value }

    var autoConnect: Boolean
        get() = state.autoConnect
        set(value) { state.autoConnect = value }

    var pathMappings: MutableMap<String, String>
        get() = state.pathMappings
        set(value) { state.pathMappings = value }

    var slowQueryThresholdMs: Int
        get() = state.slowQueryThresholdMs
        set(value) { state.slowQueryThresholdMs = value }

    var duplicateQueryThreshold: Int
        get() = state.duplicateQueryThreshold
        set(value) { state.duplicateQueryThreshold = value }

    var enableGutterIcons: Boolean
        get() = state.enableGutterIcons
        set(value) { state.enableGutterIcons = value }

    var enableInlayHints: Boolean
        get() = state.enableInlayHints
        set(value) { state.enableInlayHints = value }

    var enableNotifications: Boolean
        get() = state.enableNotifications
        set(value) { state.enableNotifications = value }

    var enableLiveTail: Boolean
        get() = state.enableLiveTail
        set(value) { state.enableLiveTail = value }

    var maxEntriesInToolWindow: Int
        get() = state.maxEntriesInToolWindow
        set(value) { state.maxEntriesInToolWindow = value }

    var notificationFilter: MutableSet<String>
        get() = state.notificationFilter
        set(value) { state.notificationFilter = value }

    companion object {
        fun getInstance(): AdpSettingsState =
            ApplicationManager.getApplication().getService(AdpSettingsState::class.java)
    }
}
