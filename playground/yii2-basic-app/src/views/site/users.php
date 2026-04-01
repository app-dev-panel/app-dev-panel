<?php

/**
 * @var yii\web\View $this
 * @var list<array{id: int, name: string, email: string, role: string}> $users
 */
$this->title = 'Users';
?>
<div class="page-header">
    <h1>Users</h1>
    <p>Demo user list. Each page load generates log entries visible in ADP.</p>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><span class="badge" style="background: var(--color-badge-bg); color: var(--color-badge-text);"><?= htmlspecialchars(
                        $user['role'],
                    ) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
