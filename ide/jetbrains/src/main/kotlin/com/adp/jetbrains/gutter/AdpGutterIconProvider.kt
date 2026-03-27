package com.adp.jetbrains.gutter

import com.adp.jetbrains.client.AdpApiClient
import com.adp.jetbrains.client.AnnotationType
import com.adp.jetbrains.client.SourceAnnotation
import com.adp.jetbrains.settings.AdpSettingsState
import com.intellij.codeInsight.daemon.LineMarkerInfo
import com.intellij.codeInsight.daemon.LineMarkerProvider
import com.intellij.icons.AllIcons
import com.intellij.openapi.editor.markup.GutterIconRenderer
import com.intellij.openapi.util.IconLoader
import com.intellij.psi.PsiElement
import com.intellij.psi.PsiFile
import com.intellij.ui.JBColor
import java.awt.*
import java.awt.geom.Ellipse2D
import javax.swing.Icon

/**
 * Provides gutter icons in the editor for lines that have associated ADP debug data.
 *
 * How it works:
 * 1. AdpDataCache maintains a file-level index: filePath → lineNumber → annotations
 * 2. When the editor repaints, IntelliJ calls collectSlowLineMarkers() for visible PSI elements
 * 3. For each element, we check if its file:line has annotations in the cache
 * 4. If yes, we create a LineMarkerInfo with an appropriate icon and tooltip
 *
 * Icon types:
 * - Red circle: Exception occurred at this line
 * - Orange circle: Slow SQL query (>threshold ms)
 * - Blue circle: SQL query (normal speed)
 * - Yellow circle: Log message (warning level)
 * - Gray circle: Log message (info/debug level)
 * - Green circle: Event dispatched from this line
 * - Purple circle: HTTP request made from this line
 * - Teal circle: Variable dump at this line
 *
 * Clicking the icon opens a popup with details:
 * - Log: level, message, timestamp
 * - Query: SQL, duration, row count, status
 * - Exception: class, message, first frame
 * - Event: event name, listener count
 * - HTTP: method, URI, status, duration
 *
 * Performance notes:
 * - Only checks the cache (O(1) lookup), never makes HTTP requests
 * - Annotations are limited to the last debug entry
 * - File path matching uses normalized paths with path mapping applied
 */
class AdpGutterIconProvider : LineMarkerProvider {

    override fun getLineMarkerInfo(element: PsiElement): LineMarkerInfo<*>? = null

    override fun collectSlowLineMarkers(
        elements: MutableList<out PsiElement>,
        result: MutableCollection<in LineMarkerInfo<*>>
    ) {
        if (!AdpSettingsState.getInstance().enableGutterIcons) return
        if (elements.isEmpty()) return

        val file = elements.first().containingFile ?: return
        val filePath = file.virtualFile?.path ?: return
        val cache = AdpApiClient.getInstance().dataCache

        val annotations = cache.getAnnotationsForFile(filePath)
        if (annotations.isEmpty()) return

        // Track which lines we've already marked to avoid duplicates
        val markedLines = mutableSetOf<Int>()

        for (element in elements) {
            // Only process first-on-line elements to avoid duplicate markers
            val document = file.viewProvider.document ?: continue
            val lineNumber = document.getLineNumber(element.textOffset) + 1 // 1-based

            if (lineNumber in markedLines) continue

            val lineAnnotations = annotations[lineNumber] ?: continue
            if (lineAnnotations.isEmpty()) continue

            markedLines.add(lineNumber)

            // Pick the highest-priority annotation for the icon
            val primary = pickPrimaryAnnotation(lineAnnotations)
            val icon = getIconForAnnotation(primary)
            val tooltip = buildTooltipHtml(lineAnnotations)

            val marker = LineMarkerInfo(
                element,
                element.textRange,
                icon,
                { tooltip },
                null, // No click navigation (handled via popup)
                GutterIconRenderer.Alignment.RIGHT,
                { "ADP debug annotation" }
            )
            result.add(marker)
        }
    }

    /**
     * Pick the most important annotation to determine the gutter icon.
     * Priority: EXCEPTION > QUERY (slow) > QUERY > HTTP_REQUEST > LOG (error) > EVENT > LOG > DUMP
     */
    private fun pickPrimaryAnnotation(annotations: List<SourceAnnotation>): SourceAnnotation {
        val priorityOrder = listOf(
            AnnotationType.EXCEPTION,
            AnnotationType.DEPRECATION,
            AnnotationType.QUERY,
            AnnotationType.HTTP_REQUEST,
            AnnotationType.EVENT,
            AnnotationType.LOG,
            AnnotationType.DUMP,
            AnnotationType.EXCEPTION_TRACE,
        )

        // Exception always wins
        annotations.find { it.type == AnnotationType.EXCEPTION }?.let { return it }

        // Slow query next
        val slowThreshold = AdpSettingsState.getInstance().slowQueryThresholdMs
        annotations.find { it.type == AnnotationType.QUERY && (it.durationMs ?: 0.0) > slowThreshold }?.let { return it }

        // Error-level log
        annotations.find { it.type == AnnotationType.LOG && it.level in listOf("error", "critical", "emergency", "alert") }?.let { return it }

        // By type priority
        for (type in priorityOrder) {
            annotations.find { it.type == type }?.let { return it }
        }

        return annotations.first()
    }

    private fun getIconForAnnotation(annotation: SourceAnnotation): Icon {
        val slowThreshold = AdpSettingsState.getInstance().slowQueryThresholdMs

        return when (annotation.type) {
            AnnotationType.EXCEPTION -> CircleIcon(JBColor(0xDC2626, 0xF87171))      // Red
            AnnotationType.EXCEPTION_TRACE -> CircleIcon(JBColor(0xF87171, 0xFCA5A5)) // Light red
            AnnotationType.DEPRECATION -> CircleIcon(JBColor(0xD97706, 0xFBBF24))     // Amber
            AnnotationType.QUERY -> {
                if ((annotation.durationMs ?: 0.0) > slowThreshold)
                    CircleIcon(JBColor(0xD97706, 0xFBBF24)) // Orange for slow
                else
                    CircleIcon(JBColor(0x2563EB, 0x60A5FA)) // Blue for normal
            }
            AnnotationType.HTTP_REQUEST -> CircleIcon(JBColor(0x7C3AED, 0xA78BFA))   // Purple
            AnnotationType.EVENT -> CircleIcon(JBColor(0x16A34A, 0x4ADE80))           // Green
            AnnotationType.LOG -> when (annotation.level) {
                "error", "critical", "emergency", "alert" -> CircleIcon(JBColor(0xDC2626, 0xF87171))
                "warning" -> CircleIcon(JBColor(0xD97706, 0xFBBF24))
                else -> CircleIcon(JBColor(0x9CA3AF, 0x6B7280))                       // Gray
            }
            AnnotationType.DUMP -> CircleIcon(JBColor(0x0D9488, 0x2DD4BF))            // Teal
            AnnotationType.CACHE -> CircleIcon(JBColor(0x0891B2, 0x22D3EE))           // Cyan
            AnnotationType.SERVICE_CALL -> CircleIcon(JBColor(0x4F46E5, 0x818CF8))    // Indigo
        }
    }

    /**
     * Build HTML tooltip showing all annotations at this line.
     * Groups by type with summary headers.
     */
    private fun buildTooltipHtml(annotations: List<SourceAnnotation>): String {
        val sb = StringBuilder("<html><body style='font-size:11px;'>")
        sb.append("<b>ADP Debug Data</b><br/>")

        // Group by type
        val grouped = annotations.groupBy { it.type }

        grouped.forEach { (type, items) ->
            sb.append("<br/><b>${formatTypeName(type)}</b> (${items.size})<br/>")
            items.take(5).forEach { annotation ->
                when (type) {
                    AnnotationType.LOG -> {
                        val levelColor = when (annotation.level) {
                            "error", "critical", "emergency", "alert" -> "#DC2626"
                            "warning" -> "#D97706"
                            else -> "#666"
                        }
                        sb.append("&nbsp;&nbsp;<span style='color:$levelColor'>[${annotation.level}]</span> ")
                        sb.append(escapeHtml(truncate(annotation.message ?: "", 80)))
                        sb.append("<br/>")
                    }
                    AnnotationType.QUERY -> {
                        val duration = String.format("%.1fms", annotation.durationMs ?: 0.0)
                        val slowThreshold = AdpSettingsState.getInstance().slowQueryThresholdMs
                        val color = if ((annotation.durationMs ?: 0.0) > slowThreshold) "#D97706" else "#2563EB"
                        sb.append("&nbsp;&nbsp;<span style='color:$color'>$duration</span> ")
                        sb.append("<code>${escapeHtml(truncate(annotation.sql ?: "", 60))}</code>")
                        sb.append("<br/>")
                    }
                    AnnotationType.EXCEPTION, AnnotationType.EXCEPTION_TRACE -> {
                        sb.append("&nbsp;&nbsp;<span style='color:#DC2626'>${escapeHtml(truncate(annotation.message ?: "", 100))}</span>")
                        sb.append("<br/>")
                    }
                    AnnotationType.HTTP_REQUEST -> {
                        val duration = String.format("%.0fms", annotation.durationMs ?: 0.0)
                        sb.append("&nbsp;&nbsp;$duration ${escapeHtml(annotation.message ?: "")}")
                        sb.append("<br/>")
                    }
                    AnnotationType.EVENT -> {
                        sb.append("&nbsp;&nbsp;${escapeHtml(annotation.message ?: "")}")
                        sb.append("<br/>")
                    }
                    AnnotationType.DEPRECATION -> {
                        sb.append("&nbsp;&nbsp;<span style='color:#D97706'>${escapeHtml(truncate(annotation.message ?: "", 100))}</span>")
                        sb.append("<br/>")
                    }
                    else -> {
                        sb.append("&nbsp;&nbsp;${escapeHtml(truncate(annotation.message ?: "", 80))}")
                        sb.append("<br/>")
                    }
                }
            }
            if (items.size > 5) {
                sb.append("&nbsp;&nbsp;<i>... and ${items.size - 5} more</i><br/>")
            }
        }

        sb.append("</body></html>")
        return sb.toString()
    }

    private fun formatTypeName(type: AnnotationType): String = when (type) {
        AnnotationType.LOG -> "Logs"
        AnnotationType.QUERY -> "SQL Queries"
        AnnotationType.EXCEPTION -> "Exceptions"
        AnnotationType.EXCEPTION_TRACE -> "Exception Stack Trace"
        AnnotationType.EVENT -> "Events"
        AnnotationType.HTTP_REQUEST -> "HTTP Requests"
        AnnotationType.DUMP -> "Variable Dumps"
        AnnotationType.DEPRECATION -> "Deprecations"
        AnnotationType.CACHE -> "Cache Operations"
        AnnotationType.SERVICE_CALL -> "Service Calls"
    }

    private fun truncate(s: String, maxLen: Int): String =
        if (s.length <= maxLen) s else s.take(maxLen) + "..."

    private fun escapeHtml(s: String): String =
        s.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;").replace("\"", "&quot;")
}

/**
 * Simple filled circle icon for gutter annotations.
 * 8x8 px filled circle with the given color.
 */
class CircleIcon(private val color: Color) : Icon {
    override fun paintIcon(c: Component?, g: Graphics, x: Int, y: Int) {
        val g2 = g.create() as Graphics2D
        g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON)
        g2.color = color
        g2.fill(Ellipse2D.Double(x + 2.0, y + 2.0, 8.0, 8.0))
        g2.dispose()
    }

    override fun getIconWidth(): Int = 12
    override fun getIconHeight(): Int = 12
}
