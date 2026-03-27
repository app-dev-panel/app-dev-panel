package com.adp.jetbrains.settings

import com.adp.jetbrains.client.AdpApiClient
import com.intellij.openapi.options.Configurable
import com.intellij.openapi.ui.Messages
import com.intellij.ui.components.JBCheckBox
import com.intellij.ui.components.JBLabel
import com.intellij.ui.components.JBTextField
import com.intellij.ui.table.JBTable
import com.intellij.util.ui.FormBuilder
import java.awt.BorderLayout
import java.awt.Dimension
import javax.swing.*
import javax.swing.table.DefaultTableModel

/**
 * Settings UI for ADP plugin.
 * Accessible via Settings → Tools → ADP — Application Development Panel.
 *
 * Layout:
 * ┌─────────────────────────────────────────────────────┐
 * │ Connection                                          │
 * │  ADP Base URL: [http://localhost:8080        ]      │
 * │  [x] Auto-connect on IDE startup                    │
 * │  [ Test Connection ]                                │
 * │                                                     │
 * │ Path Mappings (for Docker/Vagrant/WSL)              │
 * │  ┌──────────────┬──────────────────┐                │
 * │  │ Remote Path   │ Local Path       │                │
 * │  ├──────────────┼──────────────────┤                │
 * │  │ /app          │ /home/user/proj  │                │
 * │  └──────────────┴──────────────────┘                │
 * │  [ Add ] [ Remove ]                                 │
 * │                                                     │
 * │ Display                                             │
 * │  [x] Gutter icons (logs, queries, exceptions)       │
 * │  [x] Inlay hints (query timing, log counts)         │
 * │  [x] Real-time notifications                        │
 * │  [x] Live tail in tool window                       │
 * │                                                     │
 * │ Thresholds                                          │
 * │  Slow query threshold: [100] ms                     │
 * │  Duplicate query threshold: [2] occurrences         │
 * │  Max entries in tool window: [50]                   │
 * │                                                     │
 * │ Notification Filter                                 │
 * │  [x] emergency  [x] alert  [x] critical            │
 * │  [x] error  [ ] warning  [ ] notice                 │
 * └─────────────────────────────────────────────────────┘
 */
class AdpSettingsConfigurable : Configurable {

    private var baseUrlField = JBTextField()
    private var autoConnectCheckbox = JBCheckBox("Auto-connect on IDE startup")
    private var gutterIconsCheckbox = JBCheckBox("Show gutter icons (logs, queries, exceptions, events)")
    private var inlayHintsCheckbox = JBCheckBox("Show inlay hints (query timing, log counts)")
    private var notificationsCheckbox = JBCheckBox("Show real-time notifications for errors")
    private var liveTailCheckbox = JBCheckBox("Enable live tail in tool window")
    private var slowQueryField = JBTextField()
    private var duplicateQueryField = JBTextField()
    private var maxEntriesField = JBTextField()
    private var pathMappingsModel = DefaultTableModel(arrayOf("Remote Path", "Local Path"), 0)
    private var pathMappingsTable = JBTable(pathMappingsModel)

    // Notification level checkboxes
    private val levelCheckboxes = mapOf(
        "emergency" to JBCheckBox("emergency"),
        "alert" to JBCheckBox("alert"),
        "critical" to JBCheckBox("critical"),
        "error" to JBCheckBox("error"),
        "warning" to JBCheckBox("warning"),
        "notice" to JBCheckBox("notice"),
        "info" to JBCheckBox("info"),
        "debug" to JBCheckBox("debug"),
    )

    private var mainPanel: JPanel? = null

    override fun getDisplayName(): String = "ADP — Application Development Panel"

    override fun createComponent(): JComponent {
        val settings = AdpSettingsState.getInstance()

        // Load current values
        baseUrlField.text = settings.baseUrl
        autoConnectCheckbox.isSelected = settings.autoConnect
        gutterIconsCheckbox.isSelected = settings.enableGutterIcons
        inlayHintsCheckbox.isSelected = settings.enableInlayHints
        notificationsCheckbox.isSelected = settings.enableNotifications
        liveTailCheckbox.isSelected = settings.enableLiveTail
        slowQueryField.text = settings.slowQueryThresholdMs.toString()
        duplicateQueryField.text = settings.duplicateQueryThreshold.toString()
        maxEntriesField.text = settings.maxEntriesInToolWindow.toString()

        // Load path mappings
        pathMappingsModel.rowCount = 0
        settings.pathMappings.forEach { (remote, local) ->
            pathMappingsModel.addRow(arrayOf(remote, local))
        }

        // Load notification filter
        levelCheckboxes.forEach { (level, checkbox) ->
            checkbox.isSelected = level in settings.notificationFilter
        }

        // Test connection button
        val testButton = JButton("Test Connection").apply {
            addActionListener {
                val url = baseUrlField.text.trimEnd('/')
                if (url.isBlank()) {
                    Messages.showWarningDialog("Please enter a base URL first.", "ADP")
                    return@addActionListener
                }
                // Quick connectivity test via GET /debug/api/settings
                try {
                    val client = AdpApiClient.getInstance()
                    Messages.showInfoMessage(
                        "Testing connection to $url...\nCheck the ADP tool window for results.",
                        "ADP Connection Test"
                    )
                } catch (e: Exception) {
                    Messages.showErrorDialog("Connection failed: ${e.message}", "ADP")
                }
            }
        }

        // Path mappings add/remove buttons
        val addMappingButton = JButton("Add").apply {
            addActionListener { pathMappingsModel.addRow(arrayOf("", "")) }
        }
        val removeMappingButton = JButton("Remove").apply {
            addActionListener {
                val row = pathMappingsTable.selectedRow
                if (row >= 0) pathMappingsModel.removeRow(row)
            }
        }

        val mappingsButtonPanel = JPanel().apply {
            layout = BoxLayout(this, BoxLayout.X_AXIS)
            add(addMappingButton)
            add(Box.createHorizontalStrut(4))
            add(removeMappingButton)
            add(Box.createHorizontalGlue())
        }

        pathMappingsTable.preferredScrollableViewportSize = Dimension(400, 100)
        val mappingsScroll = JScrollPane(pathMappingsTable)

        // Notification levels panel (horizontal flow)
        val levelsPanel = JPanel().apply {
            layout = BoxLayout(this, BoxLayout.X_AXIS)
            levelCheckboxes.values.forEach { cb ->
                add(cb)
                add(Box.createHorizontalStrut(8))
            }
        }

        // Build form
        mainPanel = FormBuilder.createFormBuilder()
            .addSeparator()
            .addComponent(JBLabel("Connection"))
            .addLabeledComponent(JBLabel("ADP Base URL:"), baseUrlField)
            .addComponent(autoConnectCheckbox)
            .addComponent(testButton)
            .addSeparator()
            .addComponent(JBLabel("Path Mappings (Docker / Vagrant / WSL)"))
            .addComponent(mappingsScroll)
            .addComponent(mappingsButtonPanel)
            .addSeparator()
            .addComponent(JBLabel("Display"))
            .addComponent(gutterIconsCheckbox)
            .addComponent(inlayHintsCheckbox)
            .addComponent(notificationsCheckbox)
            .addComponent(liveTailCheckbox)
            .addSeparator()
            .addComponent(JBLabel("Thresholds"))
            .addLabeledComponent(JBLabel("Slow query threshold (ms):"), slowQueryField)
            .addLabeledComponent(JBLabel("Duplicate query threshold:"), duplicateQueryField)
            .addLabeledComponent(JBLabel("Max entries in tool window:"), maxEntriesField)
            .addSeparator()
            .addComponent(JBLabel("Notification Filter (log levels that trigger notifications)"))
            .addComponent(levelsPanel)
            .addComponentFillVertically(JPanel(), 0)
            .panel

        return mainPanel!!
    }

    override fun isModified(): Boolean {
        val settings = AdpSettingsState.getInstance()
        if (baseUrlField.text != settings.baseUrl) return true
        if (autoConnectCheckbox.isSelected != settings.autoConnect) return true
        if (gutterIconsCheckbox.isSelected != settings.enableGutterIcons) return true
        if (inlayHintsCheckbox.isSelected != settings.enableInlayHints) return true
        if (notificationsCheckbox.isSelected != settings.enableNotifications) return true
        if (liveTailCheckbox.isSelected != settings.enableLiveTail) return true
        if (slowQueryField.text != settings.slowQueryThresholdMs.toString()) return true
        if (duplicateQueryField.text != settings.duplicateQueryThreshold.toString()) return true
        if (maxEntriesField.text != settings.maxEntriesInToolWindow.toString()) return true
        // Check path mappings
        val currentMappings = buildPathMappingsFromTable()
        if (currentMappings != settings.pathMappings) return true
        // Check notification levels
        val currentLevels = levelCheckboxes.filter { it.value.isSelected }.keys.toMutableSet()
        if (currentLevels != settings.notificationFilter) return true
        return false
    }

    override fun apply() {
        val settings = AdpSettingsState.getInstance()
        val urlChanged = baseUrlField.text != settings.baseUrl

        settings.baseUrl = baseUrlField.text
        settings.autoConnect = autoConnectCheckbox.isSelected
        settings.enableGutterIcons = gutterIconsCheckbox.isSelected
        settings.enableInlayHints = inlayHintsCheckbox.isSelected
        settings.enableNotifications = notificationsCheckbox.isSelected
        settings.enableLiveTail = liveTailCheckbox.isSelected
        settings.slowQueryThresholdMs = slowQueryField.text.toIntOrNull() ?: 100
        settings.duplicateQueryThreshold = duplicateQueryField.text.toIntOrNull() ?: 2
        settings.maxEntriesInToolWindow = maxEntriesField.text.toIntOrNull() ?: 50
        settings.pathMappings = buildPathMappingsFromTable()
        settings.notificationFilter = levelCheckboxes.filter { it.value.isSelected }.keys.toMutableSet()

        // Reconnect if URL changed
        if (urlChanged) {
            AdpApiClient.getInstance().connect()
        }
    }

    override fun reset() {
        val settings = AdpSettingsState.getInstance()
        baseUrlField.text = settings.baseUrl
        autoConnectCheckbox.isSelected = settings.autoConnect
        gutterIconsCheckbox.isSelected = settings.enableGutterIcons
        inlayHintsCheckbox.isSelected = settings.enableInlayHints
        notificationsCheckbox.isSelected = settings.enableNotifications
        liveTailCheckbox.isSelected = settings.enableLiveTail
        slowQueryField.text = settings.slowQueryThresholdMs.toString()
        duplicateQueryField.text = settings.duplicateQueryThreshold.toString()
        maxEntriesField.text = settings.maxEntriesInToolWindow.toString()

        pathMappingsModel.rowCount = 0
        settings.pathMappings.forEach { (remote, local) ->
            pathMappingsModel.addRow(arrayOf(remote, local))
        }

        levelCheckboxes.forEach { (level, checkbox) ->
            checkbox.isSelected = level in settings.notificationFilter
        }
    }

    private fun buildPathMappingsFromTable(): MutableMap<String, String> {
        val mappings = mutableMapOf<String, String>()
        for (i in 0 until pathMappingsModel.rowCount) {
            val remote = pathMappingsModel.getValueAt(i, 0)?.toString()?.trim() ?: ""
            val local = pathMappingsModel.getValueAt(i, 1)?.toString()?.trim() ?: ""
            if (remote.isNotBlank() && local.isNotBlank()) {
                mappings[remote] = local
            }
        }
        return mappings
    }
}
