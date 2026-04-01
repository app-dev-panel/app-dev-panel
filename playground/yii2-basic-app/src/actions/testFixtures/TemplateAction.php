<?php

declare(strict_types=1);

namespace App\actions\testFixtures;

use yii\base\Action;

final class TemplateAction extends Action
{
    public function run(): array
    {
        // Render a parent view that includes a child view (nested rendering)
        // This tests TemplateCollector timing with nested EVENT_BEFORE_RENDER/EVENT_AFTER_RENDER pairs
        $output = $this->controller->renderPartial('@app/views/test-fixtures/template-parent', [
            'section' => 'main',
        ]);

        return ['fixture' => 'template:basic', 'status' => 'ok', 'rendered' => strlen($output) > 0];
    }
}
