<?php
require_once __DIR__.'/../init.php';
if($_SESSION['rol_id']!='3') exit('Acceso denegado');

$id=intval($_GET['id'] ?? 0);
$estado=$_GET['estado'] ?? '';
if($id && in_array($estado,['pendiente','en_preparacion','listo','entregado'])){
    $stmt=$mysqli->prepare("UPDATE pedidos SET estado=? WHERE id=?");
    $stmt->bind_param("si",$estado,$id);
    $stmt->execute();
    $stmt->close();
}
header('Location:index.php');
