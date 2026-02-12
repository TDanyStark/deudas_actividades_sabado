<?php

require __DIR__ . '/../../src/bootstrap.php';
require __DIR__ . '/../../src/repositories/index.php';

$today = date('Y-m-d');
$error = '';

function render_access_message(string $message): void
{
    $safe_message = h($message);
    $title = 'Acceso restringido';
    echo "<!DOCTYPE html>\n";
    echo "<html lang=\"es\">\n";
    echo "<head>\n";
    echo "    <meta charset=\"UTF-8\">\n";
    echo "    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n";
    echo "    <title>" . $title . "</title>\n";
    echo "    <link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">\n";
    echo "    <link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>\n";
    echo "    <link href=\"https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=Sora:wght@300;400;500;600&display=swap\" rel=\"stylesheet\">\n";
    echo "    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css\" rel=\"stylesheet\" integrity=\"sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH\" crossorigin=\"anonymous\">\n";
    echo "    <link href=\"" . url('/assets/app.css') . "\" rel=\"stylesheet\">\n";
    echo "</head>\n";
    echo "<body>\n";
    echo "    <main class=\"app-shell app-shell--center\">\n";
    echo "        <section class=\"app-card text-center\">\n";
    echo "            <h1 class=\"section-title\">" . $title . "</h1>\n";
    echo "            <p class=\"muted mb-0\">" . $safe_message . "</p>\n";
    echo "        </section>\n";
    echo "    </main>\n";
    echo "</body>\n";
    echo "</html>\n";
    exit;
}

if (!current_user()) {
    $token = $_GET['token'] ?? '';
    if ($token) {
        $assignment = assignment_find_by_token($token);
        if (!$assignment) {
            $error = 'Token invalido.';
        } else {
            $expires_at = $assignment['token_expires_at'];
            if ($assignment['activity_date'] !== $today) {
                $error = 'Solo puedes acceder el dia de la actividad.';
            } elseif (strtotime($expires_at) < time()) {
                $error = 'Token vencido.';
            } else {
                login_responsable($assignment, [
                    'id' => $assignment['user_id'],
                    'name' => $assignment['user_name'],
                    'email' => $assignment['user_email'],
                ]);
                redirect('/activity');
            }
        }
    }
}

if (!current_user()) {
    render_access_message($error ?: 'Necesitas un link valido para acceder.');
}

require_responsable();

$user = current_user();
if (($user['activity_date'] ?? '') !== $today) {
    render_access_message('Solo puedes acceder el dia de la actividad.');
}

$assignment_id = (int)$user['assignment_id'];
$activity = activity_find_by_assignment($assignment_id);
$notice = '';

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_activity') {
        $description = trim($_POST['description'] ?? '');
        $total_value = $_POST['total_value'] ?? null;
        $total_value = $total_value === '' ? null : (float)$total_value;

        if ($description) {
            $activity_id = activity_create($assignment_id, $description, $total_value);
            $activity = activity_find_by_assignment($assignment_id);
            $notice = 'Actividad creada.';
        }
    }

    if ($action === 'add_debtor') {
        if (!$activity) {
            $notice = 'Primero crea la actividad.';
        } else {
            $name = trim($_POST['debtor_name'] ?? '');
            $units = $_POST['units'] ?? '';
            $units = $units === '' ? null : (int)$units;
            $unit_value = $_POST['unit_value'] ?? '';
            $unit_value = $unit_value === '' ? null : (float)$unit_value;
            $amount = $_POST['amount'] ?? '';
            $amount = $amount === '' ? null : (float)$amount;
            $note = trim($_POST['note'] ?? '');
            $note = $note === '' ? null : $note;
            $activity_unit_value = $activity['total_value'] === null ? null : (float)$activity['total_value'];
            $errors = [];

            if (!$name) {
                $errors[] = 'Ingresa un nombre.';
            }

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
                debtor_create((int)$activity['id'], $name, $units, $amount, $note);
                $notice = 'Deuda registrada.';
            } elseif ($errors) {
                $notice = implode(' ', $errors);
            }
        }
    }

    if ($action === 'settle_debtor') {
        $debtor_id = (int)($_POST['debtor_id'] ?? 0);
        if ($debtor_id) {
            $remaining = debtor_remaining_amount($debtor_id);
            if ($remaining > 0) {
                payment_create($debtor_id, $remaining, 'responsable', current_user()['id']);
                $notice = 'Deuda saldada.';
            }
        }
    }
}

$debtors = $activity ? debtors_by_activity((int)$activity['id']) : [];
$debtor_names = debtor_names();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Actividad</title>
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
                <h1>Actividad del dia <?php echo h($today); ?></h1>
                <p class="muted mb-0">Responsable: <?php echo h($user['name']); ?></p>
            </div>
        </header>

        <?php if ($error): ?>
            <div class="alert alert-danger mb-4" role="alert"><?php echo h($error); ?></div>
        <?php endif; ?>

        <?php if ($notice): ?>
            <div class="alert alert-success mb-4" role="alert"><?php echo h($notice); ?></div>
        <?php endif; ?>

        <?php if (!$activity): ?>
            <section class="app-card mb-4">
                <h2 class="section-title">Crear actividad</h2>
                <form method="post" class="row g-3">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="create_activity">
                    <div class="col-md-7">
                        <label class="form-label">Descripcion</label>
                        <input type="text" name="description" class="form-control" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Valor por unidad (opcional)</label>
                        <input type="number" step="0.01" name="total_value" class="form-control">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Crear</button>
                    </div>
                </form>
            </section>
        <?php else: ?>
            <section class="app-card mb-4">
                <h2 class="section-title">Actividad: <?php echo h($activity['description']); ?></h2>
                <p class="muted mb-0">Valor por unidad: <?php echo h((string)$activity['total_value']); ?></p>
            </section>
        <?php endif; ?>

        <section class="app-card mb-4">
            <h2 class="section-title">Registrar deuda</h2>
            <form method="post" class="row g-3" data-activity-unit-value="<?php echo h((string)($activity['total_value'] ?? '')); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="add_debtor">
                <div class="col-md-4">
                    <label class="form-label">Nombre</label>
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
                <div class="col-md-2">
                    <label class="form-label">Valor por unidad (opcional)</label>
                    <input type="number" step="0.01" name="unit_value" class="form-control" placeholder="Ej: 2.50">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Total</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required>
                    <div class="form-text">Se calcula con unidades o se ingresa manualmente si no hay valor de actividad.</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Nota (opcional)</label>
                    <input type="text" name="note" class="form-control">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Agregar</button>
                </div>
            </form>
        </section>

        <section class="app-card">
            <h2 class="section-title">Deudores</h2>
            <div class="table-responsive">
                <table class="table table-dark table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Unidades</th>
                            <th>Valor</th>
                            <th>Pagado</th>
                            <th>Saldo</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($debtors as $debtor): ?>
                            <?php $remaining = (float)$debtor['amount'] - (float)$debtor['paid_total']; ?>
                            <tr>
                                <td><?php echo h($debtor['debtor_name']); ?></td>
                                <td><?php echo h((string)$debtor['units']); ?></td>
                                <td><?php echo h(number_format((float)$debtor['amount'], 2)); ?></td>
                                <td><?php echo h(number_format((float)$debtor['paid_total'], 2)); ?></td>
                                <td><?php echo h(number_format($remaining, 2)); ?></td>
                                <td>
                                    <form method="post">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="settle_debtor">
                                        <input type="hidden" name="debtor_id" value="<?php echo (int)$debtor['id']; ?>">
                                        <button type="submit" class="btn btn-outline-light btn-sm" <?php echo $remaining <= 0 ? 'disabled' : ''; ?>>Saldar</button>
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
            var form = document.querySelector('form[data-activity-unit-value]');
            if (!form) {
                return;
            }
            var unitsInput = form.querySelector('input[name="units"]');
            var unitValueInput = form.querySelector('input[name="unit_value"]');
            var amountInput = form.querySelector('input[name="amount"]');
            var activityUnitValueRaw = form.dataset.activityUnitValue || '';
            var activityUnitValue = parseFloat(activityUnitValueRaw);

            var debtorSelect = document.querySelector('.js-debtor-select');
            if (debtorSelect && !debtorSelect.tomselect && window.TomSelect) {
                new TomSelect(debtorSelect, {
                    create: true,
                    persist: false,
                    allowEmptyOption: true,
                    placeholder: 'Escribe o selecciona'
                });
            }

            function toNumber(value) {
                var parsed = parseFloat(value);
                return Number.isFinite(parsed) ? parsed : null;
            }

            function recalc() {
                var units = toNumber(unitsInput.value);
                var unitValue = toNumber(unitValueInput.value);
                var activityValue = Number.isFinite(activityUnitValue) ? activityUnitValue : null;
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

            unitsInput.addEventListener('input', recalc);
            unitValueInput.addEventListener('input', recalc);
            recalc();
        })();
    </script>
</body>
</html>
