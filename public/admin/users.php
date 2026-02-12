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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Usuarios</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=Sora:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="<?php echo url('/assets/app.css'); ?>" rel="stylesheet">
</head>
<body>
    <main class="app-shell">
        <header class="app-header mb-4">
            <div>
                <h1>Usuarios</h1>
                <p class="muted mb-0">Gestiona responsables y administradores.</p>
            </div>
            <nav class="app-nav">
                <a href="<?php echo url('/admin/assignments'); ?>">Asignaciones</a>
                <a href="<?php echo url('/admin/debts'); ?>">Deudas</a>
                <a href="<?php echo url('/admin/logout'); ?>">Salir</a>
            </nav>
        </header>

        <?php if ($notice): ?>
            <div class="alert alert-success mb-4" role="alert"><?php echo h($notice); ?></div>
        <?php endif; ?>

        <section class="app-card mb-4">
            <h2 class="section-title">Crear usuario</h2>
            <form method="post" class="row g-3">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="create">
                <div class="col-md-6">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Rol</label>
                    <select name="role" class="form-select">
                        <option value="responsable">Responsable</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Clave</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Crear</button>
                </div>
            </form>
        </section>

        <section class="app-card">
            <h2 class="section-title">Lista</h2>
            <div class="table-responsive">
                <table class="table table-dark table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Activo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <?php $form_id = 'update-user-' . (int)$user['id']; ?>
                            <tr>
                                <td>
                                    <form id="<?php echo h($form_id); ?>" method="post"></form>
                                    <input form="<?php echo h($form_id); ?>" type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
                                    <input form="<?php echo h($form_id); ?>" type="hidden" name="action" value="update">
                                    <input form="<?php echo h($form_id); ?>" type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
                                    <input form="<?php echo h($form_id); ?>" type="text" name="name" value="<?php echo h($user['name']); ?>" class="form-control form-control-sm">
                                </td>
                                <td>
                                    <input form="<?php echo h($form_id); ?>" type="email" name="email" value="<?php echo h($user['email']); ?>" class="form-control form-control-sm">
                                </td>
                                <td>
                                    <select form="<?php echo h($form_id); ?>" name="role" class="form-select form-select-sm">
                                        <option value="responsable" <?php echo $user['role'] === 'responsable' ? 'selected' : ''; ?>>Responsable</option>
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </td>
                                <td>
                                    <span class="badge text-uppercase"><?php echo (int)$user['active'] === 1 ? 'Activo' : 'Inactivo'; ?></span>
                                </td>
                                <td class="d-flex flex-wrap gap-2">
                                    <input form="<?php echo h($form_id); ?>" type="password" name="password" placeholder="Nueva clave" class="form-control form-control-sm" style="max-width: 150px;">
                                    <button form="<?php echo h($form_id); ?>" type="submit" class="btn btn-outline-light btn-sm">Guardar</button>
                                    <form method="post">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
                                        <input type="hidden" name="active" value="<?php echo (int)$user['active']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"><?php echo (int)$user['active'] === 1 ? 'Desactivar' : 'Activar'; ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
