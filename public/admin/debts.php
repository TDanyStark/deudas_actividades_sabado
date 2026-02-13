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

    if ($action === 'create_debt') {
        $activity_id = (int)($_POST['activity_id'] ?? 0);
        $name = trim($_POST['debtor_name'] ?? '');
        $units = $_POST['units'] ?? '';
        $units = $units === '' ? null : (int)$units;
        $unit_value = $_POST['unit_value'] ?? '';
        $unit_value = $unit_value === '' ? null : (float)$unit_value;
        $amount = $_POST['amount'] ?? '';
        $amount = $amount === '' ? null : (float)$amount;
        $note = trim($_POST['note'] ?? '');
        $note = $note === '' ? null : $note;
        $errors = [];

        if (!$activity_id) {
            $errors[] = 'Selecciona una actividad valida.';
        }

        if (!$name) {
            $errors[] = 'Ingresa un nombre.';
        }

        $activity = $activity_id ? activity_get($activity_id) : null;
        $activity_unit_value = $activity && $activity['total_value'] !== null ? (float)$activity['total_value'] : null;

        if ($unit_value !== null || $activity_unit_value !== null) {
            if (!$units || $units <= 0) {
                $errors[] = 'Ingresa unidades validas.';
            }
        } else {
            if ($amount === null || $amount <= 0) {
                $errors[] = 'Ingresa un valor valido.';
            }
        }

        if (!$errors) {
            if ($unit_value !== null) {
                $amount = $units * $unit_value;
            } elseif ($activity_unit_value !== null) {
                $amount = $units * $activity_unit_value;
            }
        }

        if (!$errors && $amount !== null && $amount > 0) {
            debtor_create($activity_id, $name, $units, $amount, $note);
            $notice = 'Deuda registrada.';
        } elseif ($errors) {
            $notice = implode(' ', $errors);
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
$debtor_names = debtor_names();
$activity_unit_values = [];
foreach ($activities as $activity) {
    $activity_unit_values[(int)$activity['id']] = $activity['total_value'] === null ? null : (float)$activity['total_value'];
}
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
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
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
            <h2 class="section-title">Crear deuda (admin)</h2>
            <form method="post" class="row g-3" data-activity-unit-values='<?php echo h(json_encode($activity_unit_values)); ?>'>
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="create_debt">
                <div class="col-md-4">
                    <label class="form-label">Actividad</label>
                    <select name="activity_id" class="form-select js-activity-select" required>
                        <option value="">Selecciona una actividad</option>
                        <?php foreach ($activities as $activity): ?>
                            <option value="<?php echo (int)$activity['id']; ?>">
                                <?php echo h($activity['activity_date']); ?> — <?php echo h($activity['description']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Selecciona por fecha y nombre. Se guarda el ID.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Deudor</label>
                    <select name="debtor_name" class="form-select js-debtor-select" required>
                        <option value="">Selecciona o escribe un deudor</option>
                        <?php foreach ($debtor_names as $row): ?>
                            <option value="<?php echo h($row['debtor_name']); ?>"><?php echo h($row['debtor_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Unidades</label>
                    <input type="number" name="units" min="1" class="form-control" placeholder="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Valor por unidad (opcional)</label>
                    <input type="number" step="0.01" name="unit_value" class="form-control" placeholder="Ej: 2.50">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Total</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required>
                    <div class="form-text">Se calcula con unidades o se ingresa manualmente si no hay valor de actividad.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nota (opcional)</label>
                    <input type="text" name="note" class="form-control">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Registrar deuda</button>
                </div>
            </form>
        </section>

        <section class="app-card mb-4">
            <h2 class="section-title">Filtros</h2>
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="date" value="<?php echo h($filters['date']); ?>" class="form-control">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Actividad</label>
                    <select name="activity_id" class="form-select js-activity-filter-select">
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
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
    <script>
        (function () {
            var form = document.querySelector('form[data-activity-unit-values]');
            if (!form) {
                return;
            }
            var activityInput = form.querySelector('select[name="activity_id"]');
            var unitsInput = form.querySelector('input[name="units"]');
            var unitValueInput = form.querySelector('input[name="unit_value"]');
            var amountInput = form.querySelector('input[name="amount"]');
            var activityUnitValues = {};

            var activitySelect = document.querySelector('.js-activity-select');
            if (activitySelect && !activitySelect.tomselect && window.TomSelect) {
                new TomSelect(activitySelect, {
                    create: false,
                    allowEmptyOption: true,
                    placeholder: 'Selecciona una actividad'
                });
            }

            var debtorSelect = document.querySelector('.js-debtor-select');
            if (debtorSelect && !debtorSelect.tomselect && window.TomSelect) {
                new TomSelect(debtorSelect, {
                    create: true,
                    persist: false,
                    allowEmptyOption: true,
                    placeholder: 'Selecciona un deudor',
                    render: {
                        option_create: function (data, escape) {
                            return '<div class="create">Crear <strong>' + escape(data.input) + '</strong>…</div>';
                        },
                        no_results: function () {
                            return '<div class="no-results">No se ha encontrado</div>';
                        }
                    }
                });
            }

            var filterSelect = document.querySelector('.js-activity-filter-select');
            if (filterSelect && !filterSelect.tomselect && window.TomSelect) {
                new TomSelect(filterSelect, {
                    create: false,
                    allowEmptyOption: true,
                    placeholder: 'Todas'
                });
            }

            try {
                activityUnitValues = JSON.parse(form.dataset.activityUnitValues || '{}');
            } catch (e) {
                activityUnitValues = {};
            }

            function toNumber(value) {
                var parsed = parseFloat(value);
                return Number.isFinite(parsed) ? parsed : null;
            }

            function getActivityUnitValue() {
                var activityId = parseInt(activityInput.value, 10);
                if (!Number.isFinite(activityId)) {
                    return null;
                }
                var value = activityUnitValues[activityId];
                return typeof value === 'number' ? value : null;
            }

            function recalc() {
                var units = toNumber(unitsInput.value);
                var unitValue = toNumber(unitValueInput.value);
                var activityValue = getActivityUnitValue();
                var total = null;

                if (unitValue !== null && units !== null && units > 0) {
                    total = units * unitValue;
                } else if (activityValue !== null && units !== null && units > 0) {
                    total = units * activityValue;
                }

                unitsInput.required = unitValue !== null || activityValue !== null;
                amountInput.readOnly = unitValue !== null || activityValue !== null;
                amountInput.required = unitValue === null && activityValue === null;

                if (total !== null) {
                    amountInput.value = total.toFixed(2);
                } else if (amountInput.readOnly) {
                    amountInput.value = '';
                }
            }

            activityInput.addEventListener('change', recalc);
            unitsInput.addEventListener('input', recalc);
            unitValueInput.addEventListener('input', recalc);
            recalc();
        })();
    </script>
</body>
</html>
