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
    <title>Deudas</title>
</head>
<body>
    <h1>Deudas</h1>
    <p>
        <a href="<?php echo url('/admin/users'); ?>">Usuarios</a> |
        <a href="<?php echo url('/admin/assignments'); ?>">Asignaciones</a> |
        <a href="<?php echo url('/admin/logout'); ?>">Salir</a>
    </p>

    <?php if ($notice): ?>
        <p style="color:green;"><?php echo h($notice); ?></p>
    <?php endif; ?>

    <h2>Filtros</h2>
    <form method="get">
        <label>Fecha</label>
        <input type="date" name="date" value="<?php echo h($filters['date']); ?>">
        <label>Actividad</label>
        <select name="activity_id">
            <option value="">Todas</option>
            <?php foreach ($activities as $activity): ?>
                <option value="<?php echo (int)$activity['id']; ?>" <?php echo $filters['activity_id'] == $activity['id'] ? 'selected' : ''; ?>>
                    <?php echo h($activity['description']); ?> - <?php echo h($activity['activity_date']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Filtrar</button>
    </form>

    <h2>Acciones masivas</h2>
    <form method="post" onsubmit="return confirm('Saldar todas las deudas?');">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="settle_all">
        <button type="submit">Saldar todas</button>
    </form>

    <h2>Listado</h2>
    <table border="1" cellpadding="6" cellspacing="0">
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
                <td>
                    <form method="post" style="display:inline;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="settle_debtor">
                        <input type="hidden" name="debtor_id" value="<?php echo (int)$debt['debtor_id']; ?>">
                        <button type="submit" <?php echo $remaining <= 0 ? 'disabled' : ''; ?>>Saldar</button>
                    </form>
                    <form method="post" style="display:inline;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="settle_activity">
                        <input type="hidden" name="activity_id" value="<?php echo (int)$debt['activity_id']; ?>">
                        <button type="submit" <?php echo $remaining <= 0 ? 'disabled' : ''; ?>>Saldar actividad</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
