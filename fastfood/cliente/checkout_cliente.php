<?php
require_once __DIR__ . '/../init.php';
if($_SESSION['rol_id']!='4') exit('Acceso denegado');

$uid = $_SESSION['user_id'];
$cart = $_SESSION['cart'] ?? [];
if(empty($cart)) { header('Location:index.php'); exit; }

// ✅ Definir la función crearPedido()
function crearPedido($mysqli, $nombre, $telefono, $direccion, $items, $usuario_id) {
    // 1️⃣ Insertar el pedido principal
    $estado = 'pendiente';
    $fecha = date('Y-m-d H:i:s');

    $stmt = $mysqli->prepare("INSERT INTO pedidos (usuario_id, nombre_cliente, telefono, direccion, estado, fecha) 
                              VALUES (?, ?, ?, ?, ?, ?)");
    if(!$stmt) throw new Exception("Error al preparar la inserción del pedido: " . $mysqli->error);
    $stmt->bind_param("isssss", $usuario_id, $nombre, $telefono, $direccion, $estado, $fecha);
    if(!$stmt->execute()) throw new Exception("Error al crear el pedido: " . $stmt->error);
    $pedido_id = $stmt->insert_id;
    $stmt->close();

    // 2️⃣ Insertar los productos en pedido_item
    $stmtItem = $mysqli->prepare("INSERT INTO pedido_items (pedido_id, producto_id, cantidad, precio_unit) VALUES (?, ?, ?, ?)");
    if(!$stmtItem) throw new Exception("Error al preparar inserción de items: " . $mysqli->error);

    foreach ($items as $it) {
        $producto_id = $it['producto_id'];
        $cantidad = $it['cantidad'];
        $precio_unit = $it['precio_unit'];
        $stmtItem->bind_param("iiid", $pedido_id, $producto_id, $cantidad, $precio_unit);
        if(!$stmtItem->execute()) throw new Exception("Error al insertar producto: " . $stmtItem->error);
    }
    $stmtItem->close();

    return $pedido_id;
}

$error = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $nombre = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    if($nombre === '') {
        $error = "Ingrese el nombre del cliente.";
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
            $pedido_id = crearPedido($mysqli, $nombre, $telefono, $direccion, $items, $uid);
            $_SESSION['cart'] = [];
            header("Location:index.php?mensaje=Pedido confirmado&id=$pedido_id");
            exit;
        } catch(Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<div class="container">
    <div class="header">
        <img src="../public/logo.png">
         <link rel="stylesheet" href="../css/checkout.css">
        <h2>BurgerExpress</h2>
    </div>
    <h3>Confirmar Pedido</h3>
    <?php if($error) echo "<div class='error'>$error</div>"; ?>
    <form method="post">
        <label>Nombre:</label><input name="nombre" required>
        <label>Teléfono:</label><input name="telefono">
        <label>Dirección:</label><input name="direccion">
        <button type="submit" class="btn">Confirmar Pedido</button>
    </form>
</div>
