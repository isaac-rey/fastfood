<?php
require_once __DIR__ . '/../init.php';

// Validar sesión y rol (cocina = rol_id 3)
if (!isset($_SESSION['user_id'], $_SESSION['rol_id']) || $_SESSION['rol_id'] != 3) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Acceso denegado. No tienes permisos de cocina.';
    exit;
}

$success = '';
$error = '';

// Procesar formulario de nuevo producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_producto'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);

    if ($nombre === '' || $precio <= 0) {
        $error = "Nombre y precio son obligatorios y el precio debe ser mayor a 0.";
    } else {
        // Preparar ruta de imagen
        $imagen_path = '';

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            $ext = strtolower($ext);
            // validar extensión básica
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (!in_array($ext, $allowed, true)) {
                $error = 'Tipo de archivo no permitido. Usa jpg, png, gif o webp.';
            } else {
                // crear carpeta uploads si no existe
                $uploadsDir = __DIR__ . '/../uploads';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0755, true);
                }
                $nuevoNombre = uniqid('prod_') . '.' . $ext;
                $destino = $uploadsDir . '/' . $nuevoNombre;

                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $destino)) {
                    // ruta relativa desde la web (cocina/productos.php está en /cocina/)
                    $imagen_path = 'uploads/' . $nuevoNombre;
                } else {
                    $error = 'Error al mover la imagen subida.';
                }
            }
        }

        // Insertar producto si no hubo error en imagen
        if ($error === '') {
            $stmt = $mysqli->prepare("INSERT INTO productos (nombre, descripcion, precio, imagen) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                // Asegurar que imagen_path sea cadena ('' si no hubo imagen)
                $stmt->bind_param("ssds", $nombre, $descripcion, $precio, $imagen_path);
                if ($stmt->execute()) {
                    $success = "Producto agregado correctamente.";
                } else {
                    $error = "Error al guardar el producto: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Error en la consulta: " . $mysqli->error;
            }
        }
    }
}

// Obtener lista de productos
$productos = $mysqli->query("SELECT * FROM productos ORDER BY id DESC");
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Gestionar Productos - Cocina</title>
<link rel="stylesheet" href="../css/productos.css">
<style>
.container { max-width:900px; margin:30px auto; background:#fff; padding:18px; border-radius:8px; }
h2 { margin-top:0; }
label { display:block; margin-top:8px; }
input[type="text"], input[type="number"], textarea { width:100%; padding:8px; box-sizing:border-box; }
.btn { display:inline-block; padding:8px 12px; background:#28a745; color:#fff; text-decoration:none; border-radius:6px; border:none; cursor:pointer; }
.btn.secondary { background:#007bff; }
table { width:100%; border-collapse:collapse; margin-top:14px; }
th, td { border:1px solid #e0e0e0; padding:8px; text-align:left; }
th { background:#f7f7f7; }
img.thumb { max-width:80px; max-height:60px; object-fit:cover; border-radius:4px; }
.alert { padding:8px; margin:10px 0; border-radius:6px; }
.alert.error { background:#ffe6e6; color:#cc0000; }
.alert.success { background:#e6ffea; color:#1a7f1a; }
.top-actions { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
</style>
</head>
<body>
<div class="container">
    <div class="top-actions">
        <h2>📦 Gestionar Productos</h2>
        <a href="index.php" class="btn secondary">← Volver a Pedidos</a>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="agregar_producto" value="1">
        <label>Nombre del producto:</label>
        <input type="text" name="nombre" required>

        <label>Descripción (opcional):</label>
        <textarea name="descripcion" rows="3"></textarea>

        <label>Precio (Gs):</label>
        <input type="number" name="precio" step="0.01" min="0" required>

        <label>Imagen (jpg, png, gif, webp):</label>
        <input type="file" name="imagen" accept="image/*">

        <button type="submit" class="btn">➕ Agregar Producto</button>
    </form>

    <h3>Lista de productos</h3>
    <table>
        <thead>
            <tr><th>ID</th><th>Imagen</th><th>Nombre</th><th>Precio</th></tr>
        </thead>
        <tbody>
        <?php if ($productos && $productos->num_rows > 0): ?>
            <?php while ($p = $productos->fetch_assoc()): ?>
                <tr>
                    <td><?php echo (int)$p['id']; ?></td>
                    <td>
                        <?php if (!empty($p['imagen'])): ?>
                            <img class="thumb" src="<?php echo '../' . htmlspecialchars($p['imagen']); ?>" alt="">
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                    <td>Gs <?php echo number_format($p['precio'], 0, ',', '.'); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4">No hay productos todavía.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
