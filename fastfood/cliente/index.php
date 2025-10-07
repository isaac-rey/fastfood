<?php
require_once __DIR__ . '/../init.php';

// -------------------------
// Validar acceso solo para rol cajero (rol_id = 4)
// -------------------------
if (!isset($_SESSION['user_id'], $_SESSION['rol_id']) || $_SESSION['rol_id'] !== 4) {
    exit('Acceso denegado. Solo cajeros.');
}

$uid = $_SESSION['user_id'];

// Inicializar carrito en sesión
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
$cart = &$_SESSION['cart'];

// -------------------------
// Agregar productos al carrito
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $pid = intval($_POST['producto_id']);
    $qty = max(1, intval($_POST['cantidad'])); // mínimo 1

    $found = false;
    foreach ($cart as &$c) {
        if ($c['producto_id'] === $pid) {
            $c['cantidad'] += $qty;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $stmt = $mysqli->prepare("SELECT nombre, precio FROM productos WHERE id=?");
        $stmt->bind_param("i", $pid);
        $stmt->execute();
        $stmt->bind_result($nombre, $precio);
        if ($stmt->fetch()) {
            $cart[] = [
                'producto_id' => $pid,
                'nombre'      => $nombre,
                'precio_unit' => $precio,
                'cantidad'    => $qty
            ];
        }
        $stmt->close();
    }

    $_SESSION['cart'] = $cart; // actualizar carrito en sesión
}

// -------------------------
// Obtener productos disponibles
// -------------------------
$productos = $mysqli->query("SELECT * FROM productos ORDER BY nombre ASC");

// -------------------------
// Obtener pedidos del usuario
// -------------------------
$stmt = $mysqli->prepare("SELECT id, fecha, estado FROM pedidos WHERE usuario_id=? ORDER BY fecha DESC");
$stmt->bind_param("i", $uid);
$stmt->execute();
$pedidos = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BurgerExpress - Cajero</title>

  <!-- ✅ Aquí enlazas tu CSS -->
  <link rel="stylesheet" href="../css/cliente.css">
</head>
<body>

<div class="container">
  <div class="header">
    <img src="../public/logo.png" alt="Logo">
    <h2>BurgerExpress - Cliente</h2>
    <a href="../login.php" class="btn btn-danger">Cerrar sesión</a>
  </div>

  <!-- Catálogo de Productos -->
  <h3>Catálogo de Productos</h3>
  <form method="post">
    <table>
      <tr><th>Producto</th><th>Precio</th><th>Cantidad</th><th>Acción</th></tr>
      <?php while($p = $productos->fetch_assoc()): ?>
      <tr>
          <td data-label="Producto"><?= esc($p['nombre']) ?></td>
          <td data-label="Precio">Gs <?= number_format($p['precio'], 0, ',', '.') ?></td>
          <td data-label="Cantidad"><input type="number" name="cantidad" value="1" min="1"></td>
          <td data-label="Acción">
              <button type="submit" name="add" value="1" class="btn">Agregar</button>
              <input type="hidden" name="producto_id" value="<?= $p['id'] ?>">
          </td>
      </tr>
      <?php endwhile; ?>
    </table>
  </form>

  <!-- Carrito -->
  <h3>Carrito</h3>
  <?php if (!empty($cart)):
      $total = 0;
  ?>
  <table>
    <tr><th>Producto</th><th>Cantidad</th><th>Precio Unit.</th><th>Subtotal</th></tr>
    <?php foreach($cart as $c):
        $subtotal = $c['precio_unit'] * $c['cantidad'];
        $total += $subtotal;
    ?>
    <tr>
        <td data-label="Producto"><?= esc($c['nombre']) ?></td>
        <td data-label="Cantidad"><?= $c['cantidad'] ?></td>
        <td data-label="Precio Unit.">Gs <?= number_format($c['precio_unit'],0,',','.') ?></td>
        <td data-label="Subtotal">Gs <?= number_format($subtotal,0,',','.') ?></td>
    </tr>
    <?php endforeach; ?>
    <tr>
        <td colspan="3"><b>Total</b></td>
        <td>Gs <?= number_format($total,0,',','.') ?></td>
    </tr>
  </table>
  <a href="checkout_cliente.php" class="btn">Confirmar Pedido</a>
  <?php else: ?>
  <p>El carrito está vacío.</p>
  <?php endif; ?>

  <!-- Mis Pedidos -->
  <h3>Mis Pedidos</h3>
  <?php if($pedidos->num_rows > 0): ?>
  <table>
    <tr><th>ID</th><th>Fecha</th><th>Estado</th></tr>
    <?php while($p = $pedidos->fetch_assoc()): ?>
    <tr>
        <td data-label="ID"><?= $p['id'] ?></td>
        <td data-label="Fecha"><?= $p['fecha'] ?></td>
        <td data-label="Estado"><?= esc($p['estado']) ?></td>
    </tr>
    <?php endwhile; ?>
  </table>
  <?php else: ?>
  <p>No tienes pedidos realizados.</p>
  <?php endif; ?>
</div>

<!-- ✅ Aquí enlazas tu JavaScript -->
<script src="../js/cliente.js" defer></script>
</body>
</html>
