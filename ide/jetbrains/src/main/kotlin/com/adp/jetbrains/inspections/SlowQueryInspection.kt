package com.adp.jetbrains.inspections

import com.adp.jetbrains.client.AdpApiClient
import com.adp.jetbrains.client.AnnotationType
import com.adp.jetbrains.settings.AdpSettingsState
import com.intellij.codeInspection.*
import com.intellij.psi.PsiElementVisitor
import com.intellij.psi.PsiFile

/**
 * Inspection: Slow SQL Query Detection
 *
 * Highlights lines that executed SQL queries exceeding the configured threshold
 * during the last request captured by ADP.
 *
 * How it works:
 * 1. ADP's DatabaseCollector records query start/end times and source file:line
 * 2. AdpDataCache stores pre-calculated durationMs for each query annotation
 * 3. This inspection checks QUERY annotations where durationMs > threshold
 *
 * Example warning:
 *   "Slow SQL query: 'SELECT * FROM orders JOIN ...' took 342.5ms (threshold: 100ms).
 *    Consider adding indexes or optimizing the query."
 *
 * Quick fixes:
 * - "Run EXPLAIN via ADP" — executes EXPLAIN and shows the query plan
 * - "Open in ADP Panel" — opens the database panel with this query highlighted
 * - "Copy SQL" — copies query to clipboard for testing in a database client
 *
 * Configuration:
 * - Threshold: Settings → ADP → "Slow query threshold" (default: 100ms)
 * - Disable: Settings → Inspections → ADP → "Slow SQL query detection"
 *
 * Note: This uses runtime data from the last ADP debug entry, not static analysis.
 * Queries that weren't executed during the last request won't be flagged.
 */
class SlowQueryInspection : LocalInspectionTool() {

    override fun getGroupDisplayName(): String = "ADP"
    override fun getDisplayName(): String = "Slow SQL query detection (runtime)"
    override fun getShortName(): String = "AdpSlowQuery"
    override fun isEnabledByDefault(): Boolean = true

    override fun buildVisitor(holder: ProblemsHolder, isOnTheFly: Boolean): PsiElementVisitor {
        return object : PsiElementVisitor() {
            override fun visitFile(file: PsiFile) {
                val filePath = file.virtualFile?.path ?: return
                val cache = AdpApiClient.getInstance().dataCache
                val annotations = cache.getAnnotationsForFile(filePath)
                if (annotations.isEmpty()) return

                val threshold = AdpSettingsState.getInstance().slowQueryThresholdMs

                annotations.forEach { (lineNumber, lineAnnotations) ->
                    val slowQueries = lineAnnotations.filter {
                        it.type == AnnotationType.QUERY && (it.durationMs ?: 0.0) > threshold
                    }

                    slowQueries.forEach { query ->
                        val document = file.viewProvider.document ?: return@forEach
                        if (lineNumber - 1 >= document.lineCount) return@forEach
                        val offset = document.getLineStartOffset(lineNumber - 1)
                        val element = file.findElementAt(offset) ?: return@forEach

                        val duration = String.format("%.1f", query.durationMs)
                        val truncatedSql = truncate(query.sql ?: "", 60)
                        val message = "Slow SQL query: '$truncatedSql' took ${duration}ms " +
                            "(threshold: ${threshold}ms). Consider adding indexes or optimizing."

                        holder.registerProblem(
                            element,
                            message,
                            ProblemHighlightType.WARNING,
                            ExplainQueryQuickFix(query.sql ?: ""),
                            OpenInAdpQuickFix(query.entryId, "database"),
                            CopySqlQuickFix(query.sql ?: ""),
                        )
                    }
                }
            }
        }
    }

    private fun truncate(s: String, maxLen: Int): String =
        if (s.length <= maxLen) s else s.take(maxLen) + "..."
}
