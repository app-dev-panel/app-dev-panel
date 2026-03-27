package com.adp.jetbrains.inspections

import com.adp.jetbrains.client.AdpApiClient
import com.adp.jetbrains.client.AnnotationType
import com.adp.jetbrains.settings.AdpSettingsState
import com.intellij.codeInspection.*
import com.intellij.psi.PsiElement
import com.intellij.psi.PsiElementVisitor
import com.intellij.psi.PsiFile

/**
 * Inspection: N+1 Query Detection
 *
 * Detects lines that execute the same SQL query multiple times within a single
 * request, which is a classic N+1 query problem.
 *
 * How it works:
 * 1. ADP's DatabaseCollector captures all SQL queries with their source file:line
 * 2. AdpDataCache indexes these by file:line
 * 3. This inspection checks if any line has multiple QUERY annotations with identical SQL
 * 4. If count >= duplicateQueryThreshold (default 2), shows a warning
 *
 * Example warning:
 *   "N+1 query detected: 'SELECT * FROM orders WHERE user_id = ?' executed 15 times
 *    from this line. Consider using eager loading or a JOIN to batch this query."
 *
 * Quick fixes:
 * - "Open in ADP Panel" — opens the database panel filtered to this query
 * - "Run EXPLAIN" — sends the query to ADP's /inspect/api/table/explain endpoint
 *   and shows the result in a popup
 * - "Copy SQL" — copies the full SQL to clipboard
 *
 * Configuration:
 * - Threshold: Settings → ADP → "Duplicate query threshold" (default: 2)
 * - Disable: Settings → Inspections → ADP → "N+1 query detection"
 *
 * The inspection only fires when ADP is connected and has data for the current file.
 * It does not perform static analysis — it uses actual runtime data from the last request.
 */
class NplusOneQueryInspection : LocalInspectionTool() {

    override fun getGroupDisplayName(): String = "ADP"
    override fun getDisplayName(): String = "N+1 query detection (runtime)"
    override fun getShortName(): String = "AdpNplusOneQuery"
    override fun isEnabledByDefault(): Boolean = true

    override fun buildVisitor(holder: ProblemsHolder, isOnTheFly: Boolean): PsiElementVisitor {
        return object : PsiElementVisitor() {
            private val processedLines = mutableSetOf<Int>()

            override fun visitFile(file: PsiFile) {
                val filePath = file.virtualFile?.path ?: return
                val cache = AdpApiClient.getInstance().dataCache
                val annotations = cache.getAnnotationsForFile(filePath)
                if (annotations.isEmpty()) return

                val threshold = AdpSettingsState.getInstance().duplicateQueryThreshold

                annotations.forEach { (lineNumber, lineAnnotations) ->
                    val queries = lineAnnotations.filter { it.type == AnnotationType.QUERY }
                    if (queries.size < threshold) return@forEach

                    // Group by SQL to find actual duplicates
                    val bySql = queries.groupBy { it.sql }
                    bySql.forEach { (sql, duplicates) ->
                        if (duplicates.size >= threshold) {
                            // Find PSI element at this line
                            val document = file.viewProvider.document ?: return@forEach
                            if (lineNumber - 1 >= document.lineCount) return@forEach
                            val offset = document.getLineStartOffset(lineNumber - 1)
                            val element = file.findElementAt(offset) ?: return@forEach

                            val truncatedSql = if ((sql?.length ?: 0) > 60) sql?.take(60) + "..." else sql
                            val message = "N+1 query detected: '$truncatedSql' executed ${duplicates.size} times. " +
                                "Consider eager loading or a JOIN."

                            holder.registerProblem(
                                element,
                                message,
                                ProblemHighlightType.WARNING,
                                OpenInAdpQuickFix(duplicates.first().entryId, "database"),
                                ExplainQueryQuickFix(sql ?: ""),
                                CopySqlQuickFix(sql ?: ""),
                            )
                        }
                    }
                }
            }
        }
    }
}

/**
 * Quick fix: Open the relevant ADP panel in the browser.
 */
class OpenInAdpQuickFix(
    private val entryId: String,
    private val panel: String,
) : LocalQuickFix {
    override fun getName(): String = "Open in ADP Panel"
    override fun getFamilyName(): String = "ADP"

    override fun applyFix(project: com.intellij.openapi.project.Project, descriptor: ProblemDescriptor) {
        val baseUrl = AdpSettingsState.getInstance().baseUrl.trimEnd('/')
        val url = "$baseUrl/debug/$entryId/$panel"
        try {
            java.awt.Desktop.getDesktop().browse(java.net.URI(url))
        } catch (_: Exception) {}
    }
}

/**
 * Quick fix: Run EXPLAIN on the query via ADP API and show results in a popup.
 */
class ExplainQueryQuickFix(private val sql: String) : LocalQuickFix {
    override fun getName(): String = "Run EXPLAIN via ADP"
    override fun getFamilyName(): String = "ADP"

    override fun applyFix(project: com.intellij.openapi.project.Project, descriptor: ProblemDescriptor) {
        AdpApiClient.getInstance().explainQuery(sql) { result ->
            com.intellij.openapi.application.ApplicationManager.getApplication().invokeLater {
                com.intellij.openapi.ui.Messages.showInfoMessage(
                    project,
                    result.toString(),
                    "EXPLAIN — $sql"
                )
            }
        }
    }
}

/**
 * Quick fix: Copy the full SQL query to clipboard.
 */
class CopySqlQuickFix(private val sql: String) : LocalQuickFix {
    override fun getName(): String = "Copy SQL to clipboard"
    override fun getFamilyName(): String = "ADP"

    override fun applyFix(project: com.intellij.openapi.project.Project, descriptor: ProblemDescriptor) {
        val clipboard = java.awt.Toolkit.getDefaultToolkit().systemClipboard
        clipboard.setContents(java.awt.datatransfer.StringSelection(sql), null)
    }
}
