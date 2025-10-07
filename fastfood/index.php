<?php
require_once __DIR__.'/init.php';

$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $usuario = trim($_POST['usuario'] ?? '');
    $clave   = $_POST['clave'] ?? '';
    
    $stmt = $mysqli->prepare("SELECT id, rol_id, clave FROM usuarios WHERE usuario=? LIMIT 1");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $stmt->bind_result($id, $rol_id, $hash);
    
    if($stmt->fetch() && password_verify($clave, $hash)){
        // IMPORTANTE: Usa los mismos nombres en todo el sistema
        $_SESSION['user_id'] = $id;
        $_SESSION['admin_id'] = $id; // Para compatibilidad con panel.php
        $_SESSION['rol_id']  = intval($rol_id);
        
        $stmt->close();
        
        // Redirigir según rol
        switch($rol_id){
            case 1: header('Location: admin/index.php'); break;
            case 2: header('Location: cajero/index.php'); break;
            case 3: header('Location: cocina/index.php'); break;
            case 4: header('Location: cliente/index.php'); break;
            default: 
                $error="Rol no válido."; 
                break;
        }
        exit;
    } else {
        $error="Usuario o clave incorrectos.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BurgerExpress</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>🍔 BurgerExpress</h1>
                <p>Sistema de Gestión</p>
            </div>
            
            <form method="post" class="login-form">
                <div class="form-group">
                    <label for="usuario">Usuario</label>
                    <input type="text" id="usuario" name="usuario" placeholder="Ingrese su usuario" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="clave">Contraseña</label>
                    <input type="password" id="clave" name="clave" placeholder="Ingrese su contraseña" required>
                </div>
                
                <?php if($error): ?>
                    <div class="error-message">
                        ⚠️ <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <button type="submit" class="btn-login">Iniciar Sesión</button>
                
                <div class="login-footer">
                    <a href="admin/recuperar.php">¿Olvidaste tu contraseña?</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>