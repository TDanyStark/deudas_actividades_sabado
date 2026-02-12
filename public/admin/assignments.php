<?php

require __DIR__ . '/../../src/bootstrap.php';
require __DIR__ . '/../../src/repositories/index.php';

require_admin();

$notice = '';

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $activity_date = $_POST['activity_date'] ?? '';
        if ($user_id && $activity_date) {
            assignment_create($user_id, $activity_date);
            $notice = 'Asignacion creada.';
        }
    }

    if ($action === 'regenerate') {
        $assignment_id = (int)($_POST['assignment_id'] ?? 0);
        if ($assignment_id) {
            assignment_regenerate_token($assignment_id);
            $notice = 'Token regenerado.';
        }
    }
}

$users = array_filter(user_all(), function ($user) {
    return $user['role'] === 'responsable' && (int)$user['active'] === 1;
});

$assignments = assignment_all();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignaciones</title>
</head>
<body>
    <h1>Asignaciones</h1>
    <p>
        <a href="<?php echo url('/admin/users'); ?>">Usuarios</a> |
        <a href="<?php echo url('/admin/debts'); ?>">Deudas</a> |
        <a href="<?php echo url('/admin/logout'); ?>">Salir</a>
    </p>

    <?php if ($notice): ?>
        <p style="color:green;"><?php echo h($notice); ?></p>
    <?php endif; ?>

    <h2>Nueva asignacion</h2>
    <form method="post">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="create">
        <label>Responsable</label><br>
        <select name="user_id" required>
            <?php foreach ($users as $user): ?>
                <option value="<?php echo (int)$user['id']; ?>"><?php echo h($user['name']); ?></option>
            <?php endforeach; ?>
        </select><br>
        <label>Fecha actividad</label><br>
        <input type="date" name="activity_date" required><br><br>
        <button type="submit">Asignar</button>
    </form>

    <h2>Listado</h2>
    <table border="1" cellpadding="6" cellspacing="0">
        <tr>
            <th>Fecha</th>
            <th>Responsable</th>
            <th>Email</th>
            <th>Token</th>
            <th>Link</th>
            <th>Accion</th>
        </tr>
        <?php foreach ($assignments as $assignment): ?>
            <?php
                $link = url('/activity?token=' . $assignment['token']);
            ?>
            <tr>
                <td><?php echo h($assignment['activity_date']); ?></td>
                <td><?php echo h($assignment['user_name']); ?></td>
                <td><?php echo h($assignment['user_email']); ?></td>
                <td><?php echo h($assignment['token']); ?></td>
                <td><input type="text" value="<?php echo h($link); ?>" readonly style="width:320px;"></td>
                <td>
                    <form method="post">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="regenerate">
                        <input type="hidden" name="assignment_id" value="<?php echo (int)$assignment['id']; ?>">
                        <button type="submit">Regenerar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
