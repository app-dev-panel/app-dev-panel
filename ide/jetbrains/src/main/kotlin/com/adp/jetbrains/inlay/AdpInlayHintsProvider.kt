package com.adp.jetbrains.inlay

import com.adp.jetbrains.client.AdpApiClient
import com.adp.jetbrains.client.AnnotationType
import com.adp.jetbrains.client.SourceAnnotation
import com.adp.jetbrains.settings.AdpSettingsState
import com.intellij.codeInsight.hints.*
import com.intellij.codeInsight.hints.presentation.InlayPresentation
import com.intellij.codeInsight.hints.presentation.PresentationFactory
import com.intellij.openapi.editor.Editor
import com.intellij.psi.PsiElement
import com.intellij.psi.PsiFile
import com.intellij.ui.JBColor
import java.awt.Color
import javax.swing.JPanel

/**
 * Provides inline hints at the end of lines showing ADP debug data summaries.
 *
 * Displayed as small, non-intrusive labels after the code on each line:
 *
 *   $logger->info('User login');          // ADP: 42x info
 *   $db->query('SELECT * FROM users');    // ADP: 3.2ms, 15 rows
 *   $dispatcher->dispatch($event);        // ADP: 3 listeners, 1.2ms
 *   throw new NotFoundException();        // ADP: RuntimeException: Not found
 *   $client->sendRequest($request);       // ADP: 200 OK, 145ms
 *   $cache->get('user.123');              // ADP: HIT, 0.3ms
 *
 * Hint types by annotation:
 * - LOG: "[level] message" or "Nx [level]" if multiple
 * - QUERY: "duration, N rows" + "SLOW" badge if above threshold
 * - EXCEPTION: "ClassName: message" in red
 * - EVENT: "N listeners"
 * - HTTP_REQUEST: "status, duration"
 * - CACHE: "HIT/MISS, duration"
 * - DEPRECATION: "deprecated: message"
 * - DUMP: "dump()"
 *
 * Configuration:
 * - Toggle via Settings → ADP → "Show inlay hints"
 * - Slow query threshold configurable
 *
 * Performance:
 * - Reads from in-memory AdpDataCache (no HTTP)
 * - Only processes visible lines
 * - Recalculates on daemon restart (when cache updates)
 */
@Suppress("UnstableApiUsage")
class AdpInlayHintsProvider : InlayHintsProvider<NoSettings> {

    override val key: SettingsKey<NoSettings> = SettingsKey("adp.inlay.hints")
    override val name: String = "ADP Debug Data"
    override val previewText: String = """
        ${'$'}logger->info('User logged in');
        ${'$'}db->query('SELECT * FROM users WHERE active = 1');
        ${'$'}dispatcher->dispatch(new UserCreatedEvent());
    """.trimIndent()

    override fun createSettings(): NoSettings = NoSettings()

    override fun createConfigurable(settings: NoSettings): ImmediateConfigurable =
        object : ImmediateConfigurable {
            override fun createComponent(listener: ChangeListener) = JPanel()
        }

    override fun getCollectorFor(
        file: PsiFile,
        editor: Editor,
        settings: NoSettings,
        sink: InlayHintsSink
    ): InlayHintsCollector? {
        if (!AdpSettingsState.getInstance().enableInlayHints) return null

        val filePath = file.virtualFile?.path ?: return null
        val cache = AdpApiClient.getInstance().dataCache
        val annotations = cache.getAnnotationsForFile(filePath)
        if (annotations.isEmpty()) return null

        return AdpInlayCollector(editor, annotations)
    }

    /**
     * Collects inlay hints for a single file.
     * Called by IntelliJ's daemon when the file is opened or repainted.
     */
    private class AdpInlayCollector(
        private val editor: Editor,
        private val annotations: Map<Int, List<SourceAnnotation>>,
    ) : InlayHintsCollector {

        override fun collect(element: PsiElement, editor: Editor, sink: InlayHintsSink): Boolean {
            val document = editor.document
            val lineNumber = document.getLineNumber(element.textOffset) + 1

            // Only process first element per line
            val lineStartOffset = document.getLineStartOffset(lineNumber - 1)
            if (element.textOffset != lineStartOffset) return true

            val lineAnnotations = annotations[lineNumber] ?: return true
            if (lineAnnotations.isEmpty()) return true

            val factory = PresentationFactory(editor)
            val presentation = buildPresentation(factory, lineAnnotations) ?: return true

            // Place hint at end of line
            val lineEndOffset = document.getLineEndOffset(lineNumber - 1)
            sink.addInlineElement(lineEndOffset, false, presentation, false)

            return true
        }

        /**
         * Build the inlay presentation for a set of annotations on one line.
         * Combines multiple annotations into a single compact hint.
         */
        private fun buildPresentation(
            factory: PresentationFactory,
            annotations: List<SourceAnnotation>
        ): InlayPresentation? {
            val parts = mutableListOf<InlayPresentation>()
            val slowThreshold = AdpSettingsState.getInstance().slowQueryThresholdMs

            // Prefix
            parts.add(factory.smallText("  ADP: "))

            val grouped = annotations.groupBy { it.type }
            val segments = mutableListOf<InlayPresentation>()

            // Exceptions (highest priority, shown first)
            grouped[AnnotationType.EXCEPTION]?.let { exceptions ->
                val ex = exceptions.first()
                val text = truncate(ex.message ?: "Exception", 50)
                segments.add(factory.withTooltip(
                    ex.message ?: "",
                    coloredText(factory, text, JBColor(0xDC2626, 0xF87171))
                ))
            }

            // SQL queries
            grouped[AnnotationType.QUERY]?.let { queries ->
                if (queries.size == 1) {
                    val q = queries.first()
                    val duration = String.format("%.1fms", q.durationMs ?: 0.0)
                    val isSlow = (q.durationMs ?: 0.0) > slowThreshold
                    val color = if (isSlow) JBColor(0xD97706, 0xFBBF24) else JBColor(0x2563EB, 0x60A5FA)
                    val label = if (isSlow) "$duration SLOW" else duration
                    segments.add(factory.withTooltip(
                        q.sql ?: "",
                        coloredText(factory, label, color)
                    ))
                } else {
                    val totalMs = queries.sumOf { it.durationMs ?: 0.0 }
                    val duration = String.format("%.1fms", totalMs)
                    val slowCount = queries.count { (it.durationMs ?: 0.0) > slowThreshold }
                    val label = "${queries.size} queries, $duration" +
                        if (slowCount > 0) ", $slowCount slow" else ""
                    val color = if (slowCount > 0) JBColor(0xD97706, 0xFBBF24) else JBColor(0x2563EB, 0x60A5FA)
                    segments.add(coloredText(factory, label, color))
                }
            }

            // Logs
            grouped[AnnotationType.LOG]?.let { logs ->
                if (logs.size == 1) {
                    val log = logs.first()
                    val color = logLevelColor(log.level)
                    segments.add(coloredText(factory, "[${log.level}] ${truncate(log.message ?: "", 40)}", color))
                } else {
                    val byLevel = logs.groupBy { it.level }
                    val summary = byLevel.entries.joinToString(", ") { "${it.value.size}x ${it.key}" }
                    val hasErrors = byLevel.keys.any { it in listOf("error", "critical", "emergency", "alert") }
                    val color = if (hasErrors) JBColor(0xDC2626, 0xF87171) else JBColor(0x9CA3AF, 0x6B7280)
                    segments.add(coloredText(factory, summary, color))
                }
            }

            // HTTP requests
            grouped[AnnotationType.HTTP_REQUEST]?.let { requests ->
                val r = requests.first()
                val duration = String.format("%.0fms", r.durationMs ?: 0.0)
                segments.add(coloredText(factory, "${r.message} $duration", JBColor(0x7C3AED, 0xA78BFA)))
            }

            // Events
            grouped[AnnotationType.EVENT]?.let { events ->
                segments.add(coloredText(factory, "${events.size} event(s)", JBColor(0x16A34A, 0x4ADE80)))
            }

            // Deprecations
            grouped[AnnotationType.DEPRECATION]?.let { deps ->
                segments.add(coloredText(factory, "deprecated (${deps.size})", JBColor(0xD97706, 0xFBBF24)))
            }

            // Dumps
            grouped[AnnotationType.DUMP]?.let { dumps ->
                segments.add(coloredText(factory, "dump() x${dumps.size}", JBColor(0x0D9488, 0x2DD4BF)))
            }

            if (segments.isEmpty()) return null

            // Join segments with " | " separator
            segments.forEachIndexed { index, segment ->
                parts.add(segment)
                if (index < segments.size - 1) {
                    parts.add(factory.smallText(" | "))
                }
            }

            return factory.seq(*parts.toTypedArray())
        }

        private fun coloredText(factory: PresentationFactory, text: String, color: Color): InlayPresentation {
            // Use roundWithBackground for colored text
            return factory.roundWithBackground(factory.smallText(text))
        }

        private fun logLevelColor(level: String?): Color = when (level) {
            "emergency", "alert", "critical", "error" -> JBColor(0xDC2626, 0xF87171)
            "warning" -> JBColor(0xD97706, 0xFBBF24)
            "notice" -> JBColor(0x2563EB, 0x60A5FA)
            else -> JBColor(0x9CA3AF, 0x6B7280)
        }

        private fun truncate(s: String, maxLen: Int): String =
            if (s.length <= maxLen) s else s.take(maxLen) + "..."
    }
}
