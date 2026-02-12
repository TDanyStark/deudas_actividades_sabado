<?php

require __DIR__ . '/../../src/bootstrap.php';
require __DIR__ . '/../../src/repositories/index.php';

$today = date('Y-m-d');
$error = '';

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
    echo $error ?: 'Necesitas un link valido para acceder.';
    exit;
}

require_responsable();

$user = current_user();
if (($user['activity_date'] ?? '') !== $today) {
    echo 'No tienes acceso en este dia.';
    exit;
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
            $amount = (float)($_POST['amount'] ?? 0);
            $note = trim($_POST['note'] ?? '');
            $note = $note === '' ? null : $note;

            if ($name && $amount !== 0.0) {
                debtor_create((int)$activity['id'], $name, $units, $amount, $note);
                $notice = 'Deuda registrada.';
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actividad</title>
</head>
<body>
    <h1>Actividad del dia <?php echo h($today); ?></h1>
    <p>Responsable: <?php echo h($user['name']); ?></p>

    <?php if ($error): ?>
        <p style="color:red;"><?php echo h($error); ?></p>
    <?php endif; ?>

    <?php if ($notice): ?>
        <p style="color:green;"><?php echo h($notice); ?></p>
    <?php endif; ?>

    <?php if (!$activity): ?>
        <h2>Crear actividad</h2>
        <form method="post">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="create_activity">
            <label>Descripcion</label><br>
            <input type="text" name="description" required><br>
            <label>Valor total (opcional)</label><br>
            <input type="number" step="0.01" name="total_value"><br><br>
            <button type="submit">Crear</button>
        </form>
    <?php else: ?>
        <h2>Actividad: <?php echo h($activity['description']); ?></h2>
        <p>Total: <?php echo h((string)$activity['total_value']); ?></p>
    <?php endif; ?>

    <h2>Registrar deuda</h2>
    <form method="post">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="add_debtor">
        <label>Nombre</label><br>
        <input type="text" name="debtor_name" required><br>
        <label>Unidades (opcional)</label><br>
        <input type="number" name="units"><br>
        <label>Valor</label><br>
        <input type="number" step="0.01" name="amount" required><br>
        <label>Nota (opcional)</label><br>
        <input type="text" name="note"><br><br>
        <button type="submit">Agregar</button>
    </form>

    <h2>Deudores</h2>
    <table border="1" cellpadding="6" cellspacing="0">
        <tr>
            <th>Nombre</th>
            <th>Unidades</th>
            <th>Valor</th>
            <th>Pagado</th>
            <th>Saldo</th>
            <th>Accion</th>
        </tr>
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
                        <button type="submit" <?php echo $remaining <= 0 ? 'disabled' : ''; ?>>Saldar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
