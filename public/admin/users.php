<?php

require __DIR__ . '/../../src/bootstrap.php';
require __DIR__ . '/../../src/repositories/index.php';

require_admin();

$notice = '';

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'responsable';
        $password = $_POST['password'] ?? '';

        if ($name && $email && $password) {
            user_create($name, $email, $role, $password);
            $notice = 'Usuario creado.';
        }
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'responsable';
        $password = $_POST['password'] ?? null;

        if ($id && $name && $email) {
            user_update($id, $name, $email, $role, $password);
            $notice = 'Usuario actualizado.';
        }
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $active = (int)($_POST['active'] ?? 0) === 1;
        user_set_active($id, !$active);
        $notice = 'Estado actualizado.';
    }
}

$users = user_all();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios</title>
</head>
<body>
    <h1>Usuarios</h1>
    <p>
        <a href="<?php echo url('/admin/assignments'); ?>">Asignaciones</a> |
        <a href="<?php echo url('/admin/debts'); ?>">Deudas</a> |
        <a href="<?php echo url('/admin/logout'); ?>">Salir</a>
    </p>

    <?php if ($notice): ?>
        <p style="color:green;"><?php echo h($notice); ?></p>
    <?php endif; ?>

    <h2>Crear usuario</h2>
    <form method="post">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="create">
        <label>Nombre</label><br>
        <input type="text" name="name" required><br>
        <label>Email</label><br>
        <input type="email" name="email" required><br>
        <label>Rol</label><br>
        <select name="role">
            <option value="responsable">Responsable</option>
            <option value="admin">Admin</option>
        </select><br>
        <label>Clave</label><br>
        <input type="password" name="password" required><br><br>
        <button type="submit">Crear</button>
    </form>

    <h2>Lista</h2>
    <table border="1" cellpadding="6" cellspacing="0">
        <tr>
            <th>Nombre</th>
            <th>Email</th>
            <th>Rol</th>
            <th>Activo</th>
            <th>Acciones</th>
        </tr>
        <?php foreach ($users as $user): ?>
            <?php $form_id = 'update-user-' . (int)$user['id']; ?>
            <tr>
                <td>
                    <form id="<?php echo h($form_id); ?>" method="post"></form>
                    <input form="<?php echo h($form_id); ?>" type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
                    <input form="<?php echo h($form_id); ?>" type="hidden" name="action" value="update">
                    <input form="<?php echo h($form_id); ?>" type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
                    <input form="<?php echo h($form_id); ?>" type="text" name="name" value="<?php echo h($user['name']); ?>">
                </td>
                <td>
                    <input form="<?php echo h($form_id); ?>" type="email" name="email" value="<?php echo h($user['email']); ?>">
                </td>
                <td>
                    <select form="<?php echo h($form_id); ?>" name="role">
                        <option value="responsable" <?php echo $user['role'] === 'responsable' ? 'selected' : ''; ?>>Responsable</option>
                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </td>
                <td><?php echo (int)$user['active'] === 1 ? 'Si' : 'No'; ?></td>
                <td>
                    <input form="<?php echo h($form_id); ?>" type="password" name="password" placeholder="Nueva clave">
                    <button form="<?php echo h($form_id); ?>" type="submit">Guardar</button>
                    <form method="post" style="display:inline;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
                        <input type="hidden" name="active" value="<?php echo (int)$user['active']; ?>">
                        <button type="submit"><?php echo (int)$user['active'] === 1 ? 'Desactivar' : 'Activar'; ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
