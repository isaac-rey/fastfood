<?php
require_once __DIR__.'/../init.php';

if(!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Asegurar compatibilidad
if(!isset($_SESSION['admin_id']) && isset($_SESSION['user_id'])) {
    $_SESSION['admin_id'] = $_SESSION['user_id'];
}

// Verificar que sea admin
if(isset($_SESSION['rol_id']) && $_SESSION['rol_id'] != 1) {
    header('Location: ../login.php');
    exit;
}

// Cambiar contraseña propia
$cp_error=$cp_success='';
if(isset($_POST['cambiar_clave'])){
    $actual=$_POST['actual']??'';
    $nueva=$_POST['nueva']??'';
    $repetir=$_POST['repetir']??'';

    $stmt=$mysqli->prepare("SELECT clave FROM usuarios WHERE id=?");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $stmt->bind_result($hash);
    $stmt->fetch();
    $stmt->close();

    if(!password_verify($actual,$hash)) {
        $cp_error="Clave actual incorrecta.";
    } elseif($nueva!=$repetir) {
        $cp_error="Las nuevas claves no coinciden.";
    } else {
        $n_hash=password_hash($nueva,PASSWORD_DEFAULT);
        $stmt=$mysqli->prepare("UPDATE usuarios SET clave=? WHERE id=?");
        $stmt->bind_param("si",$n_hash,$_SESSION['admin_id']);
        $stmt->execute();
        $stmt->close();
        $cp_success="Contraseña cambiada con éxito.";
    }
}

// Crear nuevo usuario
$cu_error=$cu_success='';
if(isset($_POST['crear_usuario'])){
    $usuario=trim($_POST['usuario']??'');
    $clave=$_POST['clave']??'';
    $rol_id=$_POST['rol_id']??2;

    if($usuario===''||$clave==='') {
        $cu_error="Ingrese usuario y clave.";
    } else {
        $hash=password_hash($clave,PASSWORD_DEFAULT);
        $stmt=$mysqli->prepare("INSERT INTO usuarios(usuario,clave,rol_id) VALUES(?,?,?)");
        $stmt->bind_param("ssi",$usuario,$hash,$rol_id);
        if($stmt->execute()) $cu_success="Usuario creado con éxito.";
        else $cu_error="Error: ".$mysqli->error;
        $stmt->close();
    }
}

// Pedidos
$pedidos=$mysqli->query("
    SELECT p.*, u.usuario AS cliente
    FROM pedidos p
    LEFT JOIN usuarios u ON u.id = p.usuario_id
    ORDER BY fecha DESC
    LIMIT 50
");

// Obtener lista de roles
$roles=[];
$res=$mysqli->query("SELECT id,nombre FROM rol ORDER BY id");
while($r=$res->fetch_assoc()) $roles[]=$r;

// Estadísticas
$total_pedidos = $mysqli->query("SELECT COUNT(*) as total FROM pedidos")->fetch_assoc()['total'];
$total_usuarios = $mysqli->query("SELECT COUNT(*) as total FROM usuarios")->fetch_assoc()['total'];
$total_productos = $mysqli->query("SELECT COUNT(*) as total FROM productos WHERE activo=1")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - BurgerExpress</title>
    <link rel="stylesheet" href="../public/assets.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #ff6b35;
            --primary-dark: #e55a2b;
            --secondary: #004e89;
            --success: #06d6a0;
            --danger: #ef476f;
            --warning: #ffa726;
            --dark: #1a1a2e;
            --light: #f5f5f5;
            --white: #ffffff;
            --shadow: rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--light);
            color: var(--dark);
        }
        
        /* Header */
        .admin-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 20px 0;
            box-shadow: 0 2px 10px var(--shadow);
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-content h1 {
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .btn-logout {
            background: rgba(255, 255, 255, 0.2);
            color: var(--white);
            padding: 10px 20px;
            border: 2px solid var(--white);
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-logout:hover {
            background: var(--white);
            color: var(--primary);
        }
        
        /* Container */
        .admin-container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 30px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: var(--white);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px var(--shadow);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }
        
        .stat-icon.pedidos { background: rgba(255, 107, 53, 0.1); }
        .stat-icon.usuarios { background: rgba(0, 78, 137, 0.1); }
        .stat-icon.productos { background: rgba(6, 214, 160, 0.1); }
        
        .stat-info h3 {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .stat-info p {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        /* Sections */
        .section {
            background: var(--white);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px var(--shadow);
            margin-bottom: 30px;
        }
        
        .section h2 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--dark);
            border-bottom: 3px solid var(--primary);
            padding-bottom: 10px;
        }
        
        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark);
        }
        
        .form-group input,
        .form-group select {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }
        
        /* Messages */
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .message.error {
            background: #ffebee;
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }
        
        .message.success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid var(--success);
        }
        
        /* Table */
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        table th {
            background: var(--light);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            border-bottom: 2px solid #e0e0e0;
        }
        
        table td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        table tr:hover {
            background: #fafafa;
        }
        
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge.pendiente { background: #fff3e0; color: var(--warning); }
        .badge.en_preparacion { background: #e3f2fd; color: #1976d2; }
        .badge.listo { background: #e8f5e9; color: #2e7d32; }
        .badge.entregado { background: #f3e5f5; color: #7b1fa2; }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="admin-header">
        <div class="header-content">
            <h1>🍔 Panel Administrador - BurgerExpress</h1>
            <a href="../login.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>

    <!-- Container -->
    <div class="admin-container">
        
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon pedidos">📦</div>
                <div class="stat-info">
                    <h3>Total Pedidos</h3>
                    <p><?= $total_pedidos ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon usuarios">👥</div>
                <div class="stat-info">
                    <h3>Usuarios</h3>
                    <p><?= $total_usuarios ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon productos">🍔</div>
                <div class="stat-info">
                    <h3>Productos Activos</h3>
                    <p><?= $total_productos ?></p>
                </div>
            </div>
        </div>

        <!-- Cambiar Contraseña -->
        <div class="section">
            <h2>🔒 Cambiar Contraseña</h2>
            <?php if($cp_error): ?>
                <div class="message error"><?= htmlspecialchars($cp_error) ?></div>
            <?php endif; ?>
            <?php if($cp_success): ?>
                <div class="message success"><?= htmlspecialchars($cp_success) ?></div>
            <?php endif; ?>
            
            <form method="post">
                <input type="hidden" name="cambiar_clave">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Contraseña Actual</label>
                        <input type="password" name="actual" required>
                    </div>
                    <div class="form-group">
                        <label>Nueva Contraseña</label>
                        <input type="password" name="nueva" required>
                    </div>
                    <div class="form-group">
                        <label>Repetir Nueva Contraseña</label>
                        <input type="password" name="repetir" required>
                    </div>
                </div>
                <button class="btn-primary" type="submit">Cambiar Contraseña</button>
            </form>
        </div>

        <!-- Crear Usuario -->
        <div class="section">
            <h2>➕ Crear Nuevo Usuario</h2>
            <?php if($cu_error): ?>
                <div class="message error"><?= htmlspecialchars($cu_error) ?></div>
            <?php endif; ?>
            <?php if($cu_success): ?>
                <div class="message success"><?= htmlspecialchars($cu_success) ?></div>
            <?php endif; ?>
            
            <form method="post">
                <input type="hidden" name="crear_usuario">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Usuario</label>
                        <input type="text" name="usuario" required>
                    </div>
                    <div class="form-group">
                        <label>Contraseña</label>
                        <input type="password" name="clave" required>
                    </div>
                    <div class="form-group">
                        <label>Rol</label>
                        <select name="rol_id">
                            <?php foreach($roles as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= esc($r['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button class="btn-primary" type="submit">Crear Usuario</button>
            </form>
        </div>

        <!-- Lista de Pedidos -->
        <div class="section">
            <h2>📋 Últimos Pedidos</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($p = $pedidos->fetch_assoc()):
                            $items = $mysqli->query("SELECT * FROM pedido_items WHERE pedido_id=".$p['id']);
                            $total = 0;
                            while($it = $items->fetch_assoc()) {
                                $total += $it['cantidad'] * $it['precio_unit'];
                            }
                        ?>
                        <tr>
                            <td><strong>#<?= $p['id'] ?></strong></td>
                            <td><?= esc($p['cliente'] ?: $p['nombre_cliente']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($p['fecha'])) ?></td>
                            <td><span class="badge <?= $p['estado'] ?>"><?= ucfirst(str_replace('_', ' ', $p['estado'])) ?></span></td>
                            <td><strong>Gs <?= number_format($total, 0, ',', '.') ?></strong></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>