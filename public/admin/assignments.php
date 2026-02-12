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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Asignaciones</title>
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
                <h1>Asignaciones</h1>
                <p class="muted mb-0">Crea accesos diarios para responsables.</p>
            </div>
            <nav class="app-nav">
                <a href="<?php echo url('/admin/users'); ?>">Usuarios</a>
                <a href="<?php echo url('/admin/debts'); ?>">Deudas</a>
                <a href="<?php echo url('/admin/logout'); ?>">Salir</a>
            </nav>
        </header>

        <?php if ($notice): ?>
            <div class="alert alert-success mb-4" role="alert"><?php echo h($notice); ?></div>
        <?php endif; ?>

        <section class="app-card mb-4">
            <h2 class="section-title">Nueva asignacion</h2>
            <form method="post" class="row g-3 align-items-end">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="create">
                <div class="col-md-6">
                    <label class="form-label">Responsable</label>
                    <select name="user_id" class="form-select" required>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo (int)$user['id']; ?>"><?php echo h($user['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha actividad</label>
                    <input type="date" name="activity_date" class="form-control" required>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary">Asignar</button>
                </div>
            </form>
        </section>

        <section class="app-card">
            <h2 class="section-title">Listado</h2>
            <div class="table-responsive">
                <table class="table table-dark table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Responsable</th>
                            <th>Email</th>
                            <th>Token</th>
                            <th>Link</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $assignment): ?>
                            <?php
                                $link = url('/activity?token=' . $assignment['token']);
                            ?>
                            <tr>
                                <td><?php echo h($assignment['activity_date']); ?></td>
                                <td><?php echo h($assignment['user_name']); ?></td>
                                <td><?php echo h($assignment['user_email']); ?></td>
                                <td><?php echo h($assignment['token']); ?></td>
                                <td>
                                    <input type="text" value="<?php echo h($link); ?>" readonly class="form-control form-control-sm" style="min-width: 240px;">
                                </td>
                                <td>
                                    <form method="post">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="regenerate">
                                        <input type="hidden" name="assignment_id" value="<?php echo (int)$assignment['id']; ?>">
                                        <button type="submit" class="btn btn-outline-light btn-sm">Regenerar</button>
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
