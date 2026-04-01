<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use yii\base\Action;

final class ViewAction extends Action
{
    public function run(): array
    {
        // Render a layout wrapping a view with sub-views → TemplateCollector
        // This produces a hierarchy: layout > view-with-partials > _sidebar, _content-block
        $content = $this->controller->renderPartial('@app/views/test-fixtures/view-with-partials', [
            'title' => 'ADP View Test',
        ]);
        $output = $this->controller->renderPartial('@app/views/test-fixtures/layout', [
            'content' => $content,
        ]);

        // Also render a standalone view (no nesting)
        $this->controller->renderPartial('@app/views/test-fixtures/test-view', [
            'title' => 'Standalone View',
            'items' => ['alpha', 'beta', 'gamma'],
        ]);

        return ['fixture' => 'view:basic', 'status' => 'ok', 'rendered' => strlen($output) > 0];
    }
}
