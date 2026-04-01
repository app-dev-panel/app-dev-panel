<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use yii\base\Action;

final class ViewAction extends Action
{
    public function run(): array
    {
        // Render a view to trigger VIEW_AFTER_RENDER event → TemplateCollector
        $output = $this->controller->renderPartial('@app/views/test-fixtures/test-view', [
            'title' => 'ADP View Test',
            'items' => ['alpha', 'beta', 'gamma'],
        ]);

        return ['fixture' => 'view:basic', 'status' => 'ok', 'rendered' => strlen($output) > 0];
    }
}
