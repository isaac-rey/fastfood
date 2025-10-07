<?php
require_once __DIR__.'/../init.php';

// ✅ Verificar sesión
if(!isset($_SESSION['user_id'], $_SESSION['rol_id'])) {
    exit('Acceso denegado. No has iniciado sesión.');
}

// ✅ Rol cocina = 3
if($_SESSION['rol_id'] != 3) {
    exit('Acceso denegado. No tienes permisos de cocina.');
}

// ✅ Traer pedidos pendientes o en preparación
$pedidos = $mysqli->query("
    SELECT * 
    FROM pedidos 
    WHERE estado IN('pendiente','en preparación') 
    ORDER BY fecha ASC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Pedidos en Cocina 🍳</title>
<link rel="stylesheet" href="../css/cocina.css">
</head>
<body>

<div class="container">
    <div class="top-buttons">
        <a href="productos.php" class="btn">📦 Gestionar Productos</a>
        <h2>🍳 Pedidos en Cocina</h2>
        <!-- ✅ Botón de redirección -->
        <a href="../login.php" class="btn btn-danger">Cerrar sesión</a>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Cliente</th>
            <th>Estado</th>
            <th>Acción</th>
        </tr>
        <?php while($p = $pedidos->fetch_assoc()): ?>
        <tr>
            <td><?= $p['id'] ?></td>
            <td><?= esc($p['nombre_cliente']) ?></td>
            <td><?= $p['estado'] ?></td>
            <td>
                <a href="cambiar_estado.php?id=<?= $p['id'] ?>&estado=en preparación" class="btn">👨‍🍳 En preparación</a>
                <a href="cambiar_estado.php?id=<?= $p['id'] ?>&estado=listo" class="btn">✅ Listo</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
