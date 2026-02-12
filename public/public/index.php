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
    <title>Deudas publicas</title>
</head>
<body>
    <h1>Deudas publicas</h1>

    <h2>Resumen por persona</h2>
    <table border="1" cellpadding="6" cellspacing="0">
        <tr>
            <th>Nombre</th>
            <th>Total</th>
            <th>Pagado</th>
            <th>Saldo</th>
        </tr>
        <?php foreach ($summary as $row): ?>
            <?php $remaining = (float)$row['total_amount'] - (float)$row['paid_total']; ?>
            <tr>
                <td><?php echo h($row['debtor_name']); ?></td>
                <td><?php echo h(number_format((float)$row['total_amount'], 2)); ?></td>
                <td><?php echo h(number_format((float)$row['paid_total'], 2)); ?></td>
                <td><?php echo h(number_format($remaining, 2)); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Detalle</h2>
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

    <table border="1" cellpadding="6" cellspacing="0">
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
    </table>
</body>
</html>
