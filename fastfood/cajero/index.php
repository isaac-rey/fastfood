<?php
require_once __DIR__ . '/../init.php';

// Solo cajeros
if (!isset($_SESSION['user_id'], $_SESSION['rol_id']) || $_SESSION['rol_id'] !== 2) {
    header('Location: ../login.php');
    exit;
}

$cajero_id = $_SESSION['user_id'];

// Confirmar pago
if (isset($_POST['pagar'])) {
    $pedido_id = intval($_POST['pedido_id']);
    $recibido = floatval($_POST['recibido']);
    $metodo_pago = $_POST['metodo_pago'] ?? 'efectivo';

    // Obtener total del pedido
    $stmt = $mysqli->prepare("
        SELECT SUM(i.cantidad * i.precio_unit) 
        FROM pedido_items i 
        WHERE i.pedido_id = ?
    ");
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();
    $stmt->bind_result($monto_total);
    $stmt->fetch();
    $stmt->close();

    if ($monto_total === null) {
        die("❌ Pedido no encontrado o sin items.");
    }

    $vuelto = max(0, $recibido - $monto_total);

    // Actualizar estado
    $stmt = $mysqli->prepare("UPDATE pedidos SET estado='pagado' WHERE id=?");
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();
    $stmt->close();

    // Insertar en caja
    $stmt = $mysqli->prepare("
        INSERT INTO caja (pedido_id, monto_total, metodo_pago, recibido, vuelto, cajero_id) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iddddi", $pedido_id, $monto_total, $metodo_pago, $recibido, $vuelto, $cajero_id);
    if (!$stmt->execute()) {
        die("❌ Error al registrar en caja: " . $stmt->error);
    }
    $stmt->close();

    header("Location: ticket.php?pedido_id=$pedido_id");
    exit;
}

// Cancelar pedido
if (isset($_POST['cancelar'])) {
    $pedido_id = intval($_POST['pedido_id']);
    $stmt = $mysqli->prepare("UPDATE pedidos SET estado='cancelado' WHERE id=?");
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();
    $stmt->close();
}

// Pedidos pendientes o listos
$pedidos = $mysqli->query("
    SELECT p.id, p.nombre_cliente, p.telefono, p.direccion, p.estado, p.fecha,
           SUM(i.cantidad * i.precio_unit) AS total
    FROM pedidos p
    JOIN pedido_items i ON i.pedido_id = p.id
    WHERE p.estado IN ('pendiente', 'listo')  -- <-- solo cobrables
    GROUP BY p.id
    ORDER BY p.fecha DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel Cajero - Cobros</title>
<link rel="stylesheet" href="../css/index_cajero.css">
<script>
function calcularVuelto(total, recibidoId, vueltoId) {
    const recibido = parseFloat(document.getElementById(recibidoId).value) || 0;
    const vuelto = recibido - total;
    document.getElementById(vueltoId).textContent = vuelto >= 0 ? vuelto.toFixed(0) : 0;
}
</script>
</head>
<body>
<div class="container">
    <header class="header">
        <h2>🍔 Panel Cajero - Cobros</h2>
        <a href="../index.php" class="btn btn-danger">Cerrar sesión</a>
    </header>

    <h3>📋 Pedidos Pendientes / Listos para Cobrar</h3>
    <?php if ($pedidos->num_rows > 0): ?>
    <div class="pedidos-grid">
        <?php while ($p = $pedidos->fetch_assoc()): ?>
        <div class="pedido-card <?= $p['estado'] ?>">
            <h4>Pedido #<?= $p['id'] ?></h4>
            <p><strong>Cliente:</strong> <?= esc($p['nombre_cliente']) ?></p>
            <p><strong>Tel:</strong> <?= esc($p['telefono']) ?></p>
            <p><strong>Dirección:</strong> <?= esc($p['direccion']) ?></p>
            <p><strong>Fecha:</strong> <?= $p['fecha'] ?></p>
            <p class="total"><strong>Total:</strong> Gs <?= number_format($p['total'], 0, ',', '.') ?></p>
            <p><strong>Estado:</strong> <span class="estado"><?= esc($p['estado']) ?></span></p>

            <h5>🛒 Items del pedido:</h5>
            <div class="items-grid">
            <?php
                $items = $mysqli->query("
                    SELECT i.cantidad, i.precio_unit, pr.nombre, pr.imagen
                    FROM pedido_items i
                    JOIN productos pr ON pr.id = i.producto_id
                    WHERE i.pedido_id = ".$p['id']
                );
                while ($item = $items->fetch_assoc()):
            ?>
                <div class="item-card">
                    <img src="../images/<?= esc($item['imagen']) ?>" alt="<?= esc($item['nombre']) ?>">
                    <p><?= esc($item['nombre']) ?></p>
                    <p>Cant: <?= $item['cantidad'] ?> x Gs <?= number_format($item['precio_unit'],0,',','.') ?></p>
                </div>
            <?php endwhile; ?>
            </div>

            <?php if ($p['estado'] === 'pendiente' || $p['estado'] === 'listo'): ?>
            <form method="post" class="acciones">
                <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
                <div class="form-fields">
                    <label>💳 Método de pago:</label>
                    <select name="metodo_pago" required>
                        <option value="efectivo">Efectivo</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="transferencia">Transferencia</option>
                    </select>

                    <label>💰 Monto recibido:</label>
                    <input type="number" id="recibido-<?= $p['id'] ?>" name="recibido" step="0.01" min="0" required
                           placeholder="Ingrese el monto"
                           oninput="calcularVuelto(<?= $p['total'] ?>, 'recibido-<?= $p['id'] ?>', 'vuelto-<?= $p['id'] ?>')">
                    <p>💸 Vuelto: Gs <span id="vuelto-<?= $p['id'] ?>">0</span></p>
                </div>
                <div class="btn-group">
                    <button type="submit" name="pagar" class="btn btn-success">✅ Confirmar Pago</button>
                    <button type="submit" name="cancelar" class="btn btn-warning">❌ Cancelar Pedido</button>
                </div>
            </form>
            <?php elseif ($p['estado'] === 'pagado'): ?>
                <span class="badge badge-success">✅ Pedido Pagado</span>
            <?php elseif ($p['estado'] === 'cancelado'): ?>
                <span class="badge badge-danger">❌ Pedido Cancelado</span>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
        <p class="vacio">📭 No hay pedidos pendientes ni listos</p>
    <?php endif; ?>
</div>
</body>
</html>
