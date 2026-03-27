package com.adp.jetbrains.inspections

import com.adp.jetbrains.client.AdpApiClient
import com.adp.jetbrains.client.AnnotationType
import com.intellij.codeInspection.*
import com.intellij.psi.PsiElementVisitor
import com.intellij.psi.PsiFile

/**
 * Inspection: Runtime Deprecation Detection
 *
 * Shows warnings on lines that triggered PHP deprecation notices during the last
 * request captured by ADP's DeprecationCollector.
 *
 * How it works:
 * 1. PHP triggers E_DEPRECATED / E_USER_DEPRECATED when deprecated code is called
 * 2. ADP's DeprecationCollector captures these with file:line and message
 * 3. AdpDataCache indexes them by file:line
 * 4. This inspection marks affected lines with the deprecation message
 *
 * Example warning:
 *   "Runtime deprecation: Function utf8_decode() is deprecated since PHP 8.2.
 *    Use mb_convert_encoding() instead."
 *
 * Why runtime detection matters:
 * - Static analysis can't detect deprecations in dynamically-called code
 * - Some deprecations come from libraries (only visible at runtime)
 * - Framework-specific deprecations (e.g., Symfony deprecation contracts) are only
 *   triggered at runtime
 *
 * Quick fixes:
 * - "Open in ADP Panel" — opens the deprecation panel for the debug entry
 *
 * Severity: WEAK WARNING (strikethrough style, non-blocking)
 */
class DeprecationInspection : LocalInspectionTool() {

    override fun getGroupDisplayName(): String = "ADP"
    override fun getDisplayName(): String = "Runtime deprecation detection"
    override fun getShortName(): String = "AdpDeprecation"
    override fun isEnabledByDefault(): Boolean = true

    override fun buildVisitor(holder: ProblemsHolder, isOnTheFly: Boolean): PsiElementVisitor {
        return object : PsiElementVisitor() {
            override fun visitFile(file: PsiFile) {
                val filePath = file.virtualFile?.path ?: return
                val cache = AdpApiClient.getInstance().dataCache
                val annotations = cache.getAnnotationsForFile(filePath)
                if (annotations.isEmpty()) return

                annotations.forEach { (lineNumber, lineAnnotations) ->
                    val deprecations = lineAnnotations.filter { it.type == AnnotationType.DEPRECATION }

                    deprecations.forEach { dep ->
                        val document = file.viewProvider.document ?: return@forEach
                        if (lineNumber - 1 >= document.lineCount) return@forEach
                        val offset = document.getLineStartOffset(lineNumber - 1)
                        val element = file.findElementAt(offset) ?: return@forEach

                        val message = "Runtime deprecation: ${dep.message ?: "deprecated code called"}"

                        holder.registerProblem(
                            element,
                            message,
                            ProblemHighlightType.LIKE_DEPRECATED,
                            OpenInAdpQuickFix(dep.entryId, "deprecation"),
                        )
                    }
                }
            }
        }
    }
}
