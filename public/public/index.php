<?php

require __DIR__ . '/../../src/bootstrap.php';
require __DIR__ . '/../../src/repositories/index.php';

$filters = [
    'date' => $_GET['date'] ?? '',
    'activity_id' => $_GET['activity_id'] ?? '',
];

$activities = activity_list();
$debts = debts_public_list($filters);
$summary = debts_summary_by_name();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Deudas publicas</title>
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
                <h1>Deudas publicas</h1>
                <p class="muted mb-0">Consulta saldos y movimientos por actividad.</p>
            </div>
            <div>
                <a class="btn btn-outline-light" href="<?php echo url('/admin/login'); ?>">Acceso admin</a>
            </div>
        </header>

        <section class="app-card mb-4">
            <h2 class="section-title">Resumen por persona</h2>
            <div class="table-responsive">
                <table class="table table-dark table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Total</th>
                            <th>Pagado</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($summary as $row): ?>
                            <?php $remaining = (float)$row['total_amount'] - (float)$row['paid_total']; ?>
                            <tr>
                                <td><?php echo h($row['debtor_name']); ?></td>
                                <td><?php echo h(number_format((float)$row['total_amount'], 2)); ?></td>
                                <td><?php echo h(number_format((float)$row['paid_total'], 2)); ?></td>
                                <td><?php echo h(number_format($remaining, 2)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="app-card">
            <h2 class="section-title">Detalle</h2>
            <form method="get" class="row g-3 align-items-end mb-3">
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

            <div class="table-responsive">
                <table class="table table-dark table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Actividad</th>
                            <th>Fecha</th>
                            <th>Responsable</th>
                            <th>Unidades</th>
                            <th>Valor</th>
                            <th>Pagado</th>
                            <th>Saldo</th>
                            <th>Nota</th>
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
                                <td><?php echo h((string)$debt['note']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
