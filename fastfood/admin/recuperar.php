<?php
require_once __DIR__.'/../init.php';
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $nueva   = trim($_POST['nueva'] ?? '');
    $repetir = trim($_POST['repetir'] ?? '');

    // Validaciones
    if ($nueva !== $repetir) {
        $error = "Las claves no coinciden.";
    } elseif (strlen($nueva) < 6) {
        $error = "La nueva clave debe tener al menos 6 caracteres.";
    } else {
        $hash = password_hash($nueva, PASSWORD_DEFAULT);

        // Permitir cambiar contraseña sin importar el rol
        $stmt = $mysqli->prepare("UPDATE usuarios SET clave=? WHERE usuario=?");
        $stmt->bind_param("ss", $hash, $usuario);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $success = "Contraseña cambiada con éxito. Ahora puedes iniciar sesión.";
        } else {
            $error = "Usuario no encontrado.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - BurgerExpress</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>🔒 Recuperar Contraseña</h1>
                <p>BurgerExpress</p>
            </div>
            
            <form method="post" class="login-form">
                <?php if ($error): ?>
                    <div class="error-message">⚠️ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="success" style="background: #e8f5e9; color: #2e7d32; padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #06d6a0;">
                        ✅ <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="usuario">Usuario</label>
                    <input type="text" id="usuario" name="usuario" placeholder="Ingrese su usuario" required>
                </div>

                <div class="form-group">
                    <label for="nueva">Nueva Contraseña</label>
                    <input type="password" id="nueva" name="nueva" placeholder="Mínimo 6 caracteres" required>
                </div>

                <div class="form-group">
                    <label for="repetir">Repetir Nueva Contraseña</label>
                    <input type="password" id="repetir" name="repetir" placeholder="Repita la contraseña" required>
                </div>

                <button type="submit" class="btn-login">Cambiar Contraseña</button>
                
                <div class="login-footer">
                    <a href="../login.php">← Volver al login</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>