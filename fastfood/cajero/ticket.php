<?php
require_once __DIR__ . '/../init.php';

// Verificar que venga un pedido_id
if (!isset($_GET['pedido_id'])) {
    die("❌ Pedido no especificado.");
}

$pedido_id = intval($_GET['pedido_id']);

// Obtener datos del pedido
$stmt = $mysqli->prepare("
    SELECT p.id, p.nombre_cliente, p.telefono, p.direccion, p.estado, p.fecha,
           c.monto_total, c.metodo_pago, c.recibido, c.vuelto
    FROM pedidos p
    LEFT JOIN caja c ON c.pedido_id = p.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$stmt->bind_result($id, $cliente, $telefono, $direccion, $estado, $fecha, $total, $metodo_pago, $recibido, $vuelto);
if (!$stmt->fetch()) {
    die("❌ Pedido no encontrado.");
}
$stmt->close();

// Obtener items del pedido
$items = $mysqli->query("
    SELECT i.cantidad, i.precio_unit, pr.nombre, pr.imagen
    FROM pedido_items i
    JOIN productos pr ON pr.id = i.producto_id
    WHERE i.pedido_id = $pedido_id
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ticket Pedido #<?= $id ?></title>
<style>
body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
.ticket { max-width: 400px; margin: auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
.ticket h2 { text-align: center; }
.ticket .items { margin-top: 20px; }
.ticket .item { display: flex; justify-content: space-between; margin-bottom: 10px; }
.ticket .item img { width: 50px; height: 50px; object-fit: cover; margin-right: 10px; border-radius: 5px; }
.ticket .totales { margin-top: 20px; }
.ticket .totales p { display: flex; justify-content: space-between; margin: 5px 0; }
.btn-print { display: block; text-align: center; margin-top: 20px; padding: 10px; background: #4CAF50; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
.btn-print:hover { background: #45a049; }
</style>
</head>
<body>
<div class="ticket">
    <h2>🍔 Pedido #<?= $id ?></h2>
    <p><strong>Cliente:</strong> <?= esc($cliente) ?></p>
    <p><strong>Tel:</strong> <?= esc($telefono) ?></p>
    <p><strong>Dirección:</strong> <?= esc($direccion) ?></p>
    <p><strong>Fecha:</strong> <?= $fecha ?></p>
    <p><strong>Estado:</strong> <?= esc($estado) ?></p>

    <div class="items">
        <h3>Items:</h3>
        <?php while ($item = $items->fetch_assoc()): ?>
        <div class="item">
            <div style="display:flex; align-items:center;">
                <img src="../images/<?= esc($item['imagen']) ?>" alt="<?= esc($item['nombre']) ?>">
                <span><?= esc($item['nombre']) ?> x <?= $item['cantidad'] ?></span>
            </div>
            <span>Gs <?= number_format($item['precio_unit']*$item['cantidad'],0,',','.') ?></span>
        </div>
        <?php endwhile; ?>
    </div>

    <div class="totales">
        <p><strong>Total:</strong> <span>Gs <?= number_format($total,0,',','.') ?></span></p>
        <p><strong>Método de pago:</strong> <span><?= esc($metodo_pago) ?></span></p>
        <p><strong>Recibido:</strong> <span>Gs <?= number_format($recibido,0,',','.') ?></span></p>
        <p><strong>Vuelto:</strong> <span>Gs <?= number_format($vuelto,0,',','.') ?></span></p>
    </div>

    <button class="btn-print" onclick="window.print()">🖨 Imprimir Ticket</button>
</div>
</body>
</html>
