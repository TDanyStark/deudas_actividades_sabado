<?php

require __DIR__ . '/../../src/bootstrap.php';
require __DIR__ . '/../../src/repositories/index.php';

require_admin();

$notice = '';

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'settle_debtor') {
        $debtor_id = (int)($_POST['debtor_id'] ?? 0);
        if ($debtor_id) {
            $remaining = debtor_remaining_amount($debtor_id);
            if ($remaining > 0) {
                payment_create($debtor_id, $remaining, 'admin', current_user()['id']);
                $notice = 'Deuda saldada.';
            }
        }
    }

    if ($action === 'settle_activity') {
        $activity_id = (int)($_POST['activity_id'] ?? 0);
        if ($activity_id) {
            $debtors = debtors_by_activity($activity_id);
            foreach ($debtors as $debtor) {
                $remaining = (float)$debtor['amount'] - (float)$debtor['paid_total'];
                if ($remaining > 0) {
                    payment_create((int)$debtor['id'], $remaining, 'admin', current_user()['id']);
                }
            }
            $notice = 'Deudas de la actividad saldadas.';
        }
    }

    if ($action === 'settle_all') {
        $rows = debts_public_list();
        foreach ($rows as $row) {
            $remaining = (float)$row['amount'] - (float)$row['paid_total'];
            if ($remaining > 0) {
                payment_create((int)$row['debtor_id'], $remaining, 'admin', current_user()['id']);
            }
        }
        $notice = 'Todas las deudas saldadas.';
    }
}

$filters = [
    'date' => $_GET['date'] ?? '',
    'activity_id' => $_GET['activity_id'] ?? '',
];

$activities = activity_list();
$debts = debts_public_list($filters);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Deudas</title>
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
                <h1>Deudas</h1>
                <p class="muted mb-0">Administra saldos pendientes y pagos masivos.</p>
            </div>
            <nav class="app-nav">
                <a href="<?php echo url('/admin/users'); ?>">Usuarios</a>
                <a href="<?php echo url('/admin/assignments'); ?>">Asignaciones</a>
                <a href="<?php echo url('/admin/logout'); ?>">Salir</a>
            </nav>
        </header>

        <?php if ($notice): ?>
            <div class="alert alert-success mb-4" role="alert"><?php echo h($notice); ?></div>
        <?php endif; ?>

        <section class="app-card mb-4">
            <h2 class="section-title">Filtros</h2>
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="date" value="<?php echo h($filters['date']); ?>" class="form-control">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Actividad</label>
                    <select name="activity_id" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach ($activities as $activity): ?>
                            <option value="<?php echo (int)$activity['id']; ?>" <?php echo $filters['activity_id'] == $activity['id'] ? 'selected' : ''; ?>>
                                <?php echo h($activity['description']); ?> - <?php echo h($activity['activity_date']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-outline-light">Filtrar</button>
                </div>
            </form>
        </section>

        <section class="app-card mb-4">
            <h2 class="section-title">Acciones masivas</h2>
            <form method="post" onsubmit="return confirm('Saldar todas las deudas?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="settle_all">
                <button type="submit" class="btn btn-danger">Saldar todas</button>
            </form>
        </section>

        <section class="app-card">
            <h2 class="section-title">Listado</h2>
            <div class="table-responsive">
                <table class="table table-dark table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Deudor</th>
                            <th>Actividad</th>
                            <th>Fecha</th>
                            <th>Responsable</th>
                            <th>Unidades</th>
                            <th>Monto</th>
                            <th>Pagado</th>
                            <th>Saldo</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($debts as $debt): ?>
                            <?php $remaining = (float)$debt['amount'] - (float)$debt['paid_total']; ?>
                            <tr>
                                <td><?php echo h($debt['debtor_name']); ?></td>
                                <td><?php echo h($debt['description']); ?></td>
                                <td><?php echo h($debt['activity_date']); ?></td>
                                <td><?php echo h($debt['user_name']); ?></td>
                                <td><?php echo h((string)$debt['units']); ?></td>
                                <td><?php echo h(number_format((float)$debt['amount'], 2)); ?></td>
                                <td><?php echo h(number_format((float)$debt['paid_total'], 2)); ?></td>
                                <td><?php echo h(number_format($remaining, 2)); ?></td>
                                <td class="d-flex flex-wrap gap-2">
                                    <form method="post">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="settle_debtor">
                                        <input type="hidden" name="debtor_id" value="<?php echo (int)$debt['debtor_id']; ?>">
                                        <button type="submit" class="btn btn-outline-light btn-sm" <?php echo $remaining <= 0 ? 'disabled' : ''; ?>>Saldar</button>
                                    </form>
                                    <form method="post">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="settle_activity">
                                        <input type="hidden" name="activity_id" value="<?php echo (int)$debt['activity_id']; ?>">
                                        <button type="submit" class="btn btn-outline-light btn-sm" <?php echo $remaining <= 0 ? 'disabled' : ''; ?>>Saldar actividad</button>
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
