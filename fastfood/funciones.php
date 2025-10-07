<?php
function calcularTotal($cart){
    $total=0;
    foreach($cart as $c) $total+=$c['precio_unit']*$c['cantidad'];
    return $total;
}

function esc($txt){ return htmlspecialchars($txt,ENT_QUOTES,'UTF-8'); }

function crearPedido($mysqli,$nombre,$telefono,$direccion,$items,$uid){
    $stmt=$mysqli->prepare("INSERT INTO pedidos(usuario_id,nombre_cliente,telefono,direccion) VALUES(?,?,?,?)");
    $stmt->bind_param("isss",$uid,$nombre,$telefono,$direccion);
    $stmt->execute();
    $pid=$stmt->insert_id;
    $stmt->close();

    $stmt=$mysqli->prepare("INSERT INTO pedido_items(pedido_id,producto_id,cantidad,precio_unit) VALUES(?,?,?,?)");
    foreach($items as $it){
        $stmt->bind_param("iiid",$pid,$it['producto_id'],$it['cantidad'],$it['precio_unit']);
        $stmt->execute();
    }
    $stmt->close();
    return $pid;
}
?>
