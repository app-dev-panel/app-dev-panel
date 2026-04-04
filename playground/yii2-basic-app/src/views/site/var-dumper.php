<?php

/** @var yii\web\View $this */
/** @var bool $success */

$this->title = 'Var Dumper';
?>

<div class="page-header">
    <h1>Var Dumper</h1>
    <p>Dump a sample variable to inspect its structure in the ADP panel.</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        Variable dumped successfully. Open the <a href="/debug/">Debug Panel</a> to inspect it.
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-title">Dump Sample Data</div>
    <p style="margin-bottom: 16px; font-size: 14px; color: var(--color-text-secondary);">
        Click the button below to dump a sample data structure. The dumped variable will be captured by ADP
        and can be inspected in the Debug Panel.
    </p>
    <div class="code-block" style="margin-bottom: 16px;">[
    'string' => 'Hello from ADP Playground!',
    'integer' => 42,
    'float' => 3.14,
    'boolean' => true,
    'null_value' => null,
    'array' => ['apples', 'oranges', 'bananas'],
    'nested' => [
        'user' => [
            'id' => 1,
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'roles' => ['admin', 'editor'],
        ],
        'metadata' => [
            'created_at' => '2026-04-04T12:00:00Z',
            'version' => '1.0.0',
        ],
    ],
]</div>
    <form method="POST">
        <input type="hidden" name="<?= \Yii::$app->request->csrfParam ?>" value="<?= \Yii::$app->request->csrfToken ?>">
        <button type="submit" class="btn btn-primary">Dump Variable</button>
    </form>
</div>
