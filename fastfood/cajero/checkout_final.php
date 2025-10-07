<?php
require_once __DIR__.'/../init.php';
require_once __DIR__.'/../funciones.php';

// Verificar sesión y rol
if(!isset($_SESSION['user_id']) || $_SESSION['rol_id'] != 2) {
    header('Location: ../login.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];
if(empty($cart)) { 
    header('Location: index.php'); 
    exit; 
}

$total = calcularTotal($cart);
$error = '';
$vuelto = null;

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $nombre = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $efectivo = floatval($_POST['efectivo']);

    if($nombre === '') {
        $error = "Ingrese el nombre del cliente.";
    } elseif($efectivo < $total) {
        $error = "El efectivo ingresado es menor al total.";
    } else {
        $items = [];
        foreach($cart as $it) {
            $items[] = [
                'producto_id' => $it['producto_id'],
                'cantidad' => $it['cantidad'],
                'precio_unit' => $it['precio_unit']
            ];
        }
        
        try {
            $pedido_id = crearPedido($mysqli, $nombre, $telefono, $direccion, $items, $_SESSION['user_id']);
            $vuelto = $efectivo - $total;
            $_SESSION['cart'] = [];
        } catch(Exception $e) { 
            $error = $e->getMessage(); 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - BurgerExpress</title>
    <link rel="stylesheet" href="../css/global.css">
</head>
<body>
<div class="container">
    <h2>💰 Cajero - Checkout</h2>

    <?php if($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if($vuelto !== null): ?>
        <div class="success">
            <h3>✅ Pedido Confirmado</h3>
            <p><strong>Total:</strong> Gs <?= number_format($total, 0, ',', '.') ?></p>
            <p><strong>Efectivo recibido:</strong> Gs <?= number_format($efectivo, 0, ',', '.') ?></p>
            <p><strong>Vuelto:</strong> <span style="color: var(--success); font-size: 1.5rem;">Gs <?= number_format($vuelto, 0, ',', '.') ?></span></p>
            <a href="index.php" class="btn">Nuevo Pedido</a>
        </div>
    <?php else: ?>
        <div class="card">
            <h3>Resumen del Pedido</h3>
            <table>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unit.</th>
                    <th>Subtotal</th>
                </tr>
                <?php foreach($cart as $item): ?>
                <tr>
                    <td><?= esc($item['nombre']) ?></td>
                    <td><?= $item['cantidad'] ?></td>
                    <td>Gs <?= number_format($item['precio_unit'], 0, ',', '.') ?></td>
                    <td>Gs <?= number_format($item['precio_unit'] * $item['cantidad'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3"><strong>TOTAL</strong></td>
                    <td><strong>Gs <?= number_format($total, 0, ',', '.') ?></strong></td>
                </tr>
            </table>
        </div>

        <form method="post">
            <h3>Datos del Cliente</h3>
            <label>Nombre Cliente *</label>
            <input name="nombre" required>
            
            <label>Teléfono</label>
            <input name="telefono" type="tel">
            
            <label>Dirección</label>
            <input name="direccion">
            
            <h3>Pago</h3>
            <label><strong>Total a Pagar: Gs <?= number_format($total, 0, ',', '.') ?></strong></label>
            <label>Efectivo Recibido *</label>
            <input type="number" name="efectivo" step="1" min="<?= $total ?>" required placeholder="<?= $total ?>">
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-success">Confirmar Pago</button>
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    <?php endif; ?>
</div>
</body>
</html>