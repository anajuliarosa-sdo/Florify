<?php
require_once __DIR__.'/config/db.php'; exigir_admin(); if($_SERVER['REQUEST_METHOD']==='POST'){$id=(int)($_POST['id']??0);$status=$_POST['status']??'';if(in_array($status,['Pendente','Preparação','Entregue'],true)){$q=$pdo->prepare('UPDATE pedido SET status_pedido=? WHERE id_pedido=?');$q->execute([$status,$id]);}}header('Location: admin.php#pedidos');exit;
